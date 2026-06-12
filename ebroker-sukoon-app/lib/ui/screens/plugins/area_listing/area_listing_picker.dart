import 'package:ebroker/data/model/area_listing_models.dart';
import 'package:ebroker/data/repositories/area_listing_repository.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/utils/area_listing_context.dart';
import 'package:ebroker/utils/area_listing_save_helper.dart';
import 'package:flutter/material.dart';

/// Area / sub-area picker aligned with website [AreaSubAreaSelector].
/// When [showStateCity] is false, only area + sub-area are shown (city/state come from map).
class AreaListingPicker extends StatefulWidget {
  const AreaListingPicker({
    required this.onChanged,
    this.initialSelection,
    this.country = 'India',
    this.showStateCity = true,
    this.requiresCity = false,
    this.parentCityName,
    this.parentStateName,
    this.suggestOnlyMode = false,
    super.key,
  });

  final ValueChanged<AreaListingSelection> onChanged;
  final AreaListingSelection? initialSelection;
  final String country;
  final bool showStateCity;
  final bool requiresCity;
  final String? parentCityName;
  final String? parentStateName;

  /// When true, never call create-area APIs; custom names are suggest-on-save only.
  final bool suggestOnlyMode;

  @override
  State<AreaListingPicker> createState() => _AreaListingPickerState();
}

class _AreaListingPickerState extends State<AreaListingPicker> {
  final AreaListingRepository _repository = AreaListingRepository();

  List<AreaListingItem> _states = [];
  List<AreaListingItem> _cities = [];
  List<AreaListingItem> _areas = [];
  List<AreaListingSubArea> _subAreas = [];

  AreaListingItem? _selectedState;
  AreaListingItem? _selectedCity;
  AreaListingItem? _selectedArea;
  AreaListingSubArea? _selectedSubArea;

  bool _loadingStates = true;
  bool _loadingCities = false;
  bool _loadingAreas = false;
  bool _loadingSubAreas = false;
  bool _canManage = false;
  bool _savingArea = false;
  bool _savingSubArea = false;

  final TextEditingController _customAreaController = TextEditingController();
  final TextEditingController _customSubAreaController =
      TextEditingController();

  /// Invalidates in-flight async work when the widget is disposed or reloaded.
  int _loadGeneration = 0;

  String get _effectiveCity =>
      (widget.parentCityName ?? widget.initialSelection?.cityName ?? '').trim();

  String get _effectiveState =>
      (widget.parentStateName ?? widget.initialSelection?.stateName ?? '').trim();

  bool get _hasCityContext => _effectiveCity.isNotEmpty;

  bool get _isSuggestOnly => widget.suggestOnlyMode;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      unawaited(_loadPermissions());
      unawaited(_bootstrap());
    });
  }

  @override
  void dispose() {
    _loadGeneration++;
    _customAreaController.dispose();
    _customSubAreaController.dispose();
    super.dispose();
  }

  int _beginLoad() => ++_loadGeneration;

  bool _isLoadCurrent(int token) => mounted && token == _loadGeneration;

  void _safeSetState(VoidCallback fn) {
    if (!mounted) return;
    setState(fn);
  }

  Future<void> _loadPermissions() async {
    if (_isSuggestOnly) return;
    if (!HiveUtils.isUserAgent() || !ActiveRoleManager.isAgent) {
      if (_canManage) _safeSetState(() => _canManage = false);
      return;
    }
    try {
      final allowed = await _repository.fetchCanManageAreaListing();
      if (!mounted) return;
      if (allowed != _canManage) {
        _safeSetState(() => _canManage = allowed);
      }
    } on Exception {
      // Non-agents and failed permission checks use suggest flow.
    }
  }

  bool _selectionChanged(
    AreaListingSelection? previous,
    AreaListingSelection? current,
  ) {
    if (previous == null && current == null) return false;
    if (previous == null || current == null) return true;
    return previous.detectedAreaName != current.detectedAreaName ||
        previous.detectedSubAreaName != current.detectedSubAreaName ||
        previous.areaId != current.areaId ||
        previous.subAreaId != current.subAreaId ||
        previous.areaName != current.areaName ||
        previous.subAreaName != current.subAreaName ||
        previous.cityId != current.cityId;
  }

  @override
  void didUpdateWidget(AreaListingPicker oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.showStateCity) return;

    final cityChanged = oldWidget.parentCityName != widget.parentCityName ||
        oldWidget.parentStateName != widget.parentStateName;
    final selectionChanged = _selectionChanged(
      oldWidget.initialSelection,
      widget.initialSelection,
    );

    if (cityChanged || selectionChanged) {
      _customAreaController.clear();
      _customSubAreaController.clear();
      final token = _beginLoad();
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!_isLoadCurrent(token)) return;
        unawaited(
          _bootstrapFromParentLocation(
            clearAreaOnReload: true,
            token: token,
          ),
        );
      });
    }
  }

  Future<void> _bootstrap() async {
    final token = _beginLoad();
    if (!widget.showStateCity) {
      await _bootstrapFromParentLocation(
        clearAreaOnReload: false,
        token: token,
      );
      return;
    }

    try {
      _states = await _repository.fetchStates(country: widget.country);
      if (!_isLoadCurrent(token)) return;

      final initial = widget.initialSelection;
      if (initial?.stateId != null) {
        _selectedState = _findItemById(_states, initial!.stateId!);
      } else if ((initial?.stateName ?? '').isNotEmpty) {
        _selectedState = _matchByName(_states, initial!.stateName!);
      }
      _safeSetState(() => _loadingStates = false);
      if (!_isLoadCurrent(token)) return;

      if (_selectedState != null) {
        await _loadCities(
          state: _selectedState!,
          cityId: initial?.cityId,
          token: token,
        );
        if (!_isLoadCurrent(token)) return;
        if (_selectedCity != null) {
          await _loadAreas(
            city: _selectedCity!,
            areaId: initial?.areaId,
            token: token,
          );
          if (!_isLoadCurrent(token)) return;
          await _applyInitialSelection(initial, token: token);
        }
      }
      if (!_isLoadCurrent(token)) return;
      _notify(
        userConfirmed: widget.initialSelection?.userConfirmed ??
            (_selectedArea != null),
      );
    } on Exception catch (_) {
      if (!_isLoadCurrent(token)) return;
      _safeSetState(() => _loadingStates = false);
    }
  }

  Future<void> _bootstrapFromParentLocation({
    bool clearAreaOnReload = false,
    int? token,
  }) async {
    final loadToken = token ?? _beginLoad();
    if (!_isLoadCurrent(loadToken)) return;

    _safeSetState(() {
      _loadingStates = false;
      _loadingAreas = true;
      if (clearAreaOnReload) {
        _selectedArea = null;
        _selectedSubArea = null;
        _subAreas = [];
      }
    });

    if (!_hasCityContext) {
      if (!_isLoadCurrent(loadToken)) return;
      _safeSetState(() => _loadingAreas = false);
      _notify(userConfirmed: false);
      return;
    }

    try {
      _states = await _repository.fetchStates(country: widget.country);
      if (!_isLoadCurrent(loadToken)) return;

      _selectedState = _matchByName(_states, _effectiveState);
      if (_selectedState != null) {
        await _loadCities(
          state: _selectedState!,
          cityId: clearAreaOnReload ? null : widget.initialSelection?.cityId,
          token: loadToken,
        );
      } else {
        _cities = await _repository.fetchCities(
          state: _effectiveState,
          country: widget.country,
        );
        if (!_isLoadCurrent(loadToken)) return;
        _selectedCity = _matchByName(_cities, _effectiveCity);
      }

      final initial = widget.initialSelection;
      if (_selectedCity != null) {
        await _loadAreas(
          city: _selectedCity!,
          areaId: clearAreaOnReload ? null : initial?.areaId,
          token: loadToken,
        );
        if (!_isLoadCurrent(loadToken)) return;
        await _applyInitialSelection(initial, token: loadToken);
      }

      if (!_isLoadCurrent(loadToken)) return;
      _safeSetState(() => _loadingAreas = false);

      final initialSel = widget.initialSelection;
      final confirmed = initialSel?.userConfirmed == true ||
          (_selectedArea != null &&
              initialSel?.areaId != null &&
              initialSel!.areaId! > 0);
      _notify(userConfirmed: confirmed);
    } on Exception catch (_) {
      if (!_isLoadCurrent(loadToken)) return;
      _safeSetState(() => _loadingAreas = false);
    }
  }

  AreaListingItem? _findItemById(List<AreaListingItem> items, int id) {
    for (final item in items) {
      if (item.id == id) return item;
    }
    return null;
  }

  AreaListingSubArea? _findSubAreaById(List<AreaListingSubArea> items, int id) {
    for (final item in items) {
      if (item.id == id) return item;
    }
    return null;
  }

  AreaListingSubArea? _matchSubAreaByName(
    List<AreaListingSubArea> items,
    String name,
  ) {
    final target = name.trim().toLowerCase();
    if (target.isEmpty) return null;
    for (final item in items) {
      if (item.name.trim().toLowerCase() == target) {
        return item;
      }
    }
    return null;
  }

  Future<void> _applyInitialSelection(
    AreaListingSelection? initial, {
    required int token,
  }) async {
    if (initial == null || !_isLoadCurrent(token)) return;

    _selectAreaFromInitial(initial);
    if (_selectedArea != null) {
      await _loadSubAreas(
        area: _selectedArea!,
        subAreaId: initial.subAreaId,
        token: token,
      );
      if (!_isLoadCurrent(token)) return;
      _selectSubAreaFromInitial(initial);
    } else {
      final areaLabel =
          (initial.areaName ?? initial.detectedAreaName ?? '').trim();
      if (areaLabel.isNotEmpty) {
        _customAreaController.text = areaLabel;
      }
      await _applyDetectedNameHints(initial, token: token);
    }

    final subLabel =
        (initial.subAreaName ?? initial.detectedSubAreaName ?? '').trim();
    if (_selectedSubArea == null && subLabel.isNotEmpty) {
      _customSubAreaController.text = subLabel;
    }

    if (!_isLoadCurrent(token)) return;
    _safeSetState(() {});
  }

  void _selectAreaFromInitial(AreaListingSelection initial) {
    if (initial.areaId != null && initial.areaId! > 0) {
      _selectedArea = _findItemById(_areas, initial.areaId!) ??
          _matchByName(_areas, initial.areaName ?? '');
      final name = (initial.areaName ?? '').trim();
      if (_selectedArea == null && name.isNotEmpty) {
        _selectedArea = AreaListingItem(id: initial.areaId!, name: name);
        if (!_areas.any((item) => item.id == _selectedArea!.id)) {
          _areas = [..._areas, _selectedArea!];
        }
      }
    } else {
      final name =
          (initial.areaName ?? initial.detectedAreaName ?? '').trim();
      if (name.isNotEmpty) {
        _selectedArea = _matchByName(_areas, name);
      }
    }
  }

  void _selectSubAreaFromInitial(AreaListingSelection initial) {
    if (initial.subAreaId != null && initial.subAreaId! > 0) {
      _selectedSubArea = _findSubAreaById(_subAreas, initial.subAreaId!) ??
          _matchSubAreaByName(_subAreas, initial.subAreaName ?? '');
      final name = (initial.subAreaName ?? '').trim();
      if (_selectedSubArea == null && name.isNotEmpty) {
        _selectedSubArea = AreaListingSubArea(
          id: initial.subAreaId!,
          areaId: _selectedArea?.id ?? initial.areaId ?? 0,
          name: name,
        );
        if (!_subAreas.any((item) => item.id == _selectedSubArea!.id)) {
          _subAreas = [..._subAreas, _selectedSubArea!];
        }
      }
    } else {
      final name =
          (initial.subAreaName ?? initial.detectedSubAreaName ?? '').trim();
      if (name.isNotEmpty) {
        _selectedSubArea = _matchSubAreaByName(_subAreas, name);
      }
    }
  }

  Future<void> _applyDetectedNameHints(
    AreaListingSelection? initial, {
    required int token,
  }) async {
    if (!_isLoadCurrent(token)) return;

    final detectedArea = (initial?.detectedAreaName ?? initial?.areaName ?? '')
        .trim();
    final detectedSub =
        (initial?.detectedSubAreaName ?? initial?.subAreaName ?? '').trim();
    if (detectedArea.isEmpty && detectedSub.isEmpty) return;

    if (_selectedArea == null && detectedArea.isNotEmpty) {
      _selectedArea = _matchByName(_areas, detectedArea);
      if (_selectedArea != null) {
        await _loadSubAreas(area: _selectedArea!, token: token);
      }
    }

    if (!_isLoadCurrent(token)) return;

    if (_selectedArea == null && detectedSub.isNotEmpty) {
      for (final area in _areas) {
        await _loadSubAreas(area: area, token: token);
        if (!_isLoadCurrent(token)) return;
        final matchedSub = _matchSubAreaByName(_subAreas, detectedSub);
        if (matchedSub != null) {
          _selectedArea = area;
          _selectedSubArea = matchedSub;
          break;
        }
      }
    } else if (_selectedArea != null &&
        _selectedSubArea == null &&
        detectedSub.isNotEmpty) {
      await _loadSubAreas(area: _selectedArea!, token: token);
      if (!_isLoadCurrent(token)) return;
      _selectedSubArea = _matchSubAreaByName(_subAreas, detectedSub);
    }

    if (_selectedArea != null) {
      await _loadSubAreas(
        area: _selectedArea!,
        subAreaId: _selectedSubArea?.id,
        token: token,
      );
    }

    if (!_isLoadCurrent(token)) return;
    _safeSetState(() {});
  }

  AreaListingItem? _matchByName(List<AreaListingItem> items, String name) {
    final target = name.trim().toLowerCase();
    if (target.isEmpty) return null;
    for (final item in items) {
      if (item.name.trim().toLowerCase() == target) {
        return item;
      }
    }
    return null;
  }

  void _notify({required bool userConfirmed}) {
    if (!mounted) return;

    final stateName = (_selectedState?.name ?? '').isNotEmpty
        ? _selectedState!.name
        : (_effectiveState.isNotEmpty
            ? _effectiveState
            : widget.initialSelection?.stateName);
    final cityName = (_selectedCity?.name ?? '').isNotEmpty
        ? _selectedCity!.name
        : (_effectiveCity.isNotEmpty
            ? _effectiveCity
            : widget.initialSelection?.cityName);

    final pickedAreaName = _selectedArea?.name ??
        widget.initialSelection?.areaName;
    final pickedSubName = _selectedSubArea?.name ??
        widget.initialSelection?.subAreaName;
    final detectedArea = userConfirmed && (pickedAreaName ?? '').isNotEmpty
        ? pickedAreaName
        : widget.initialSelection?.detectedAreaName ??
            widget.initialSelection?.areaName;
    final detectedSub = userConfirmed && (pickedSubName ?? '').isNotEmpty
        ? pickedSubName
        : widget.initialSelection?.detectedSubAreaName ??
            widget.initialSelection?.subAreaName;

    widget.onChanged(
      AreaListingSelection(
        stateId: _selectedState?.id ?? widget.initialSelection?.stateId,
        stateName: stateName,
        cityId: _selectedCity?.id ?? widget.initialSelection?.cityId,
        cityName: cityName,
        countryName: widget.country,
        areaId: _selectedArea?.id ?? widget.initialSelection?.areaId,
        areaName: pickedAreaName,
        subAreaId: _selectedSubArea?.id ?? widget.initialSelection?.subAreaId,
        subAreaName: pickedSubName,
        detectedAreaName: detectedArea,
        detectedSubAreaName: detectedSub,
        manualAddress: widget.initialSelection?.manualAddress,
        source: widget.initialSelection?.source ?? 'mobile',
        userConfirmed: userConfirmed,
      ),
    );
  }

  void _applyCustomAreaSuggestion(String name, {bool showToast = true}) {
    if (!mounted) return;
    _safeSetState(() {
      _selectedArea = null;
      _selectedSubArea = null;
      _subAreas = [];
      _customAreaController.clear();
    });
    widget.onChanged(
      AreaListingSelection(
        stateId: _selectedState?.id ?? widget.initialSelection?.stateId,
        stateName: _effectiveState.isNotEmpty
            ? _effectiveState
            : widget.initialSelection?.stateName,
        cityId: _selectedCity?.id ?? widget.initialSelection?.cityId,
        cityName: _effectiveCity.isNotEmpty
            ? _effectiveCity
            : widget.initialSelection?.cityName,
        countryName: widget.country,
        areaName: name,
        detectedAreaName: name,
        source: widget.initialSelection?.source ?? 'google',
        userConfirmed: false,
        manualAddress: widget.initialSelection?.manualAddress,
      ),
    );
    if (showToast && mounted) {
      HelperUtils.showSnackBarMessage(
        context,
        'areaSubmitOnSave'.translate(context),
      );
    }
  }

  String? _resolveCityForSuggest() {
    final city = _effectiveCity.isNotEmpty
        ? _effectiveCity
        : (widget.initialSelection?.cityName ?? '').trim();
    return city.isEmpty ? null : city;
  }

  String? _resolveStateForSuggest() {
    final state = _effectiveState.isNotEmpty
        ? _effectiveState
        : (widget.initialSelection?.stateName ?? '').trim();
    return state.isEmpty ? null : state;
  }

  Future<void> _suggestAreaFromCustomField(String name) async {
    final city = _resolveCityForSuggest();
    if (city == null) {
      HelperUtils.showSnackBarMessage(
        context,
        'selectCityFirstForArea'.translate(context),
      );
      return;
    }
    _safeSetState(() => _savingArea = true);
    try {
      await _repository.suggestArea(
        name: name,
        city: city,
        state: _resolveStateForSuggest(),
        country: widget.country,
      );
      if (!mounted) return;
      _applyCustomAreaSuggestion(name, showToast: false);
      HelperUtils.showSnackBarMessage(
        context,
        'areaSuggestionSent'.translate(context),
      );
    } on Exception catch (e) {
      if (mounted) {
        HelperUtils.showSnackBarMessage(
          context,
          e.toString(),
        );
      }
    } finally {
      if (mounted) _safeSetState(() => _savingArea = false);
    }
  }

  Future<void> _addAreaFromCustomField() async {
    final name = AreaListingSaveHelper.titleCaseLocation(
      _customAreaController.text,
    );
    if (name.isEmpty || _savingArea) return;

    if (_isSuggestOnly || !_canManage) {
      await _suggestAreaFromCustomField(name);
      return;
    }

    _safeSetState(() => _savingArea = true);
    try {
      final created = await _repository.createArea(
        name: name,
        cityId: _selectedCity?.id ?? widget.initialSelection?.cityId,
        city: _effectiveCity.isNotEmpty
            ? _effectiveCity
            : widget.initialSelection?.cityName,
        state: _effectiveState.isNotEmpty
            ? _effectiveState
            : widget.initialSelection?.stateName,
        country: widget.country,
      );
      if (!mounted) return;
      if (created != null && created.id > 0 && _selectedCity != null) {
        final token = _beginLoad();
        await _loadAreas(city: _selectedCity!, areaId: created.id, token: token);
        if (!_isLoadCurrent(token)) return;
        _safeSetState(() {
          _selectedArea = created;
          _selectedSubArea = null;
          _customAreaController.clear();
        });
        await _loadSubAreas(area: created, token: token);
        if (!_isLoadCurrent(token)) return;
        _notify(userConfirmed: true);
        HelperUtils.showSnackBarMessage(
          context,
          'areaAddedSuccess'.translate(context),
        );
      } else {
        HelperUtils.showSnackBarMessage(
          context,
          'areaAddFailed'.translate(context),
        );
      }
    } on Exception catch (e) {
      if (mounted) HelperUtils.showSnackBarMessage(context, e.toString());
    } finally {
      if (mounted) _safeSetState(() => _savingArea = false);
    }
  }

  void _applyCustomSubAreaSuggestion(String name, {bool showToast = true}) {
    final areaLabel = (_selectedArea?.name ??
            widget.initialSelection?.suggestedAreaLabel ??
            widget.initialSelection?.areaName ??
            widget.initialSelection?.detectedAreaName ??
            '')
        .trim();
    if (areaLabel.isEmpty) {
      HelperUtils.showSnackBarMessage(
        context,
        'selectAreaFirstForSubArea'.translate(context),
      );
      return;
    }
    if (!mounted) return;
    _safeSetState(() {
      _selectedSubArea = null;
      _customSubAreaController.clear();
    });
    widget.onChanged(
      AreaListingSelection(
        stateId: _selectedState?.id ?? widget.initialSelection?.stateId,
        stateName: widget.initialSelection?.stateName,
        cityId: _selectedCity?.id ?? widget.initialSelection?.cityId,
        cityName: widget.initialSelection?.cityName,
        countryName: widget.country,
        areaId: _selectedArea?.id ?? widget.initialSelection?.areaId,
        areaName: _selectedArea?.name ?? widget.initialSelection?.areaName,
        detectedAreaName:
            widget.initialSelection?.detectedAreaName ?? areaLabel,
        subAreaName: name,
        detectedSubAreaName: name,
        source: widget.initialSelection?.source ?? 'google',
        userConfirmed: _selectedArea != null,
        manualAddress: widget.initialSelection?.manualAddress,
      ),
    );
    if (showToast && mounted) {
      HelperUtils.showSnackBarMessage(
        context,
        'areaSubmitOnSave'.translate(context),
      );
    }
  }

  Future<void> _suggestSubAreaFromCustomField(String name) async {
    final areaId = _selectedArea?.id ?? widget.initialSelection?.areaId;
    _safeSetState(() => _savingSubArea = true);
    try {
      if (areaId != null && areaId > 0) {
        await _repository.suggestSubArea(areaId: areaId, name: name);
      } else {
        final areaLabel = (_selectedArea?.name ??
                widget.initialSelection?.suggestedAreaLabel ??
                widget.initialSelection?.areaName ??
                widget.initialSelection?.detectedAreaName ??
                '')
            .trim();
        if (areaLabel.isEmpty) {
          HelperUtils.showSnackBarMessage(
            context,
            'selectAreaFirstForSubArea'.translate(context),
          );
          return;
        }
        final city = _resolveCityForSuggest();
        if (city == null) {
          HelperUtils.showSnackBarMessage(
            context,
            'selectCityFirstForArea'.translate(context),
          );
          return;
        }
        await _repository.suggestArea(
          name: areaLabel,
          city: city,
          state: _resolveStateForSuggest(),
          country: widget.country,
          subAreaName: name,
          areaId: areaId,
        );
      }
      if (!mounted) return;
      _applyCustomSubAreaSuggestion(name, showToast: false);
      HelperUtils.showSnackBarMessage(
        context,
        'subAreaSuggestionSent'.translate(context),
      );
    } on Exception catch (e) {
      if (mounted) {
        HelperUtils.showSnackBarMessage(
          context,
          e.toString(),
        );
      }
    } finally {
      if (mounted) _safeSetState(() => _savingSubArea = false);
    }
  }

  Future<void> _addSubAreaFromCustomField() async {
    final name = AreaListingSaveHelper.titleCaseLocation(
      _customSubAreaController.text,
    );
    if (name.isEmpty || _savingSubArea) return;

    final areaId = _selectedArea?.id ?? widget.initialSelection?.areaId;

    if (_isSuggestOnly || !_canManage) {
      await _suggestSubAreaFromCustomField(name);
      return;
    }

    if (areaId == null || areaId == 0) {
      await _suggestSubAreaFromCustomField(name);
      return;
    }

    _safeSetState(() => _savingSubArea = true);
    try {
      final created = await _repository.createSubArea(
        areaId: areaId,
        name: name,
      );
      if (!mounted) return;
      if (created != null && created.id > 0 && _selectedArea != null) {
        final token = _beginLoad();
        await _loadSubAreas(
          area: _selectedArea!,
          subAreaId: created.id,
          token: token,
        );
        if (!_isLoadCurrent(token)) return;
        _safeSetState(() {
          _selectedSubArea = created;
          _customSubAreaController.clear();
        });
        _notify(userConfirmed: true);
        HelperUtils.showSnackBarMessage(
          context,
          'subAreaAddedSuccess'.translate(context),
        );
      } else {
        HelperUtils.showSnackBarMessage(
          context,
          'subAreaAddFailed'.translate(context),
        );
      }
    } on Exception catch (e) {
      if (mounted) HelperUtils.showSnackBarMessage(context, e.toString());
    } finally {
      if (mounted) _safeSetState(() => _savingSubArea = false);
    }
  }

  Future<void> _loadCities({
    required AreaListingItem state,
    int? cityId,
    required int token,
  }) async {
    if (!_isLoadCurrent(token)) return;
    _safeSetState(() => _loadingCities = true);
    try {
      _cities = await _repository.fetchCities(
        stateId: state.id,
        state: state.name,
        country: widget.country,
      );
      if (cityId != null) {
        _selectedCity = _findItemById(_cities, cityId);
      } else if (_effectiveCity.isNotEmpty) {
        _selectedCity = _matchByName(_cities, _effectiveCity);
      }
    } on Exception catch (_) {
      _cities = [];
    }
    if (!_isLoadCurrent(token)) return;
    _safeSetState(() => _loadingCities = false);
  }

  Future<void> _loadAreas({
    required AreaListingItem city,
    int? areaId,
    required int token,
  }) async {
    if (!_isLoadCurrent(token)) return;
    _safeSetState(() => _loadingAreas = true);
    try {
      _areas = await _repository.fetchAreas(
        cityId: city.id,
        city: city.name,
        stateId: _selectedState?.id,
      );
      if (areaId != null) {
        _selectedArea = _findItemById(_areas, areaId);
      }
    } on Exception catch (_) {
      _areas = [];
    }
    if (!_isLoadCurrent(token)) return;
    _safeSetState(() => _loadingAreas = false);
  }

  Future<void> _loadSubAreas({
    required AreaListingItem area,
    int? subAreaId,
    required int token,
  }) async {
    if (!_isLoadCurrent(token)) return;
    _safeSetState(() => _loadingSubAreas = true);
    try {
      _subAreas = await _repository.fetchSubAreas(areaId: area.id);
      if (subAreaId != null) {
        _selectedSubArea = _findSubAreaById(_subAreas, subAreaId);
      }
    } on Exception catch (_) {
      _subAreas = [];
    }
    if (!_isLoadCurrent(token)) return;
    _safeSetState(() => _loadingSubAreas = false);
  }

  String _displayName(String name) =>
      AreaListingSaveHelper.titleCaseLocation(name);

  Widget _sectionLabel(BuildContext context, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: CustomText(
        text,
        fontSize: context.font.sm,
        fontWeight: FontWeight.w500,
        color: context.color.textColorDark,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final cityRequiredButMissing = widget.requiresCity && !_hasCityContext;

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: context.color.secondaryColor,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: context.color.borderColor),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          CustomText(
            widget.showStateCity
                ? 'areaListingTitleFull'.translate(context)
                : 'areaListingTitle'.translate(context),
            fontWeight: FontWeight.w600,
            fontSize: context.font.md,
            color: context.color.textColorDark,
          ),
          const SizedBox(height: 6),
          CustomText(
            widget.showStateCity
                ? 'areaListingHelpFull'.translate(context)
                : 'areaListingHelpCompact'.translate(context),
            fontSize: context.font.sm,
            color: context.color.textLightColor,
            maxLines: 4,
          ),
          if (cityRequiredButMissing) ...[
            const SizedBox(height: 10),
            CustomText(
              'areaListingMapCityFirst'.translate(context),
              fontSize: context.font.sm,
              color: Colors.orange.shade700,
              maxLines: 4,
            ),
          ],
          if (!cityRequiredButMissing &&
              (widget.initialSelection?.suggestedAreaLabel ?? '').isNotEmpty) ...[
            const SizedBox(height: 10),
            CustomText(
              'detectedFromMap'
                  .translate(context)
                  .replaceAll(
                    '{area}',
                    _displayName(
                      widget.initialSelection!.suggestedAreaLabel,
                    ),
                  )
                  .replaceAll(
                    '{sub}',
                    widget.initialSelection!.suggestedSubAreaLabel.isNotEmpty
                        ? ', ${_displayName(widget.initialSelection!.suggestedSubAreaLabel)}'
                        : '',
                  ),
              fontSize: context.font.sm,
              color: context.color.tertiaryColor,
              maxLines: 4,
            ),
          ],
          const SizedBox(height: 12),
          UiUtils.getDivider(context),
          const SizedBox(height: 12),
          if (widget.showStateCity) ...[
            _dropdown<AreaListingItem>(
              context,
              label: 'state'.translate(context),
              value: _selectedState,
              items: _states,
              isLoading: _loadingStates,
              itemLabel: (item) => _displayName(item.name),
              onChanged: (value) async {
                if (value == null || !mounted) return;
                final token = _beginLoad();
                _safeSetState(() {
                  _selectedState = value;
                  _selectedCity = null;
                  _selectedArea = null;
                  _selectedSubArea = null;
                  _cities = [];
                  _areas = [];
                  _subAreas = [];
                });
                await _loadCities(state: value, token: token);
                if (!_isLoadCurrent(token)) return;
                _notify(userConfirmed: false);
              },
            ),
            const SizedBox(height: 12),
            _dropdown<AreaListingItem>(
              context,
              label: 'city'.translate(context),
              value: _selectedCity,
              items: _cities,
              isLoading: _loadingCities,
              enabled: _selectedState != null,
              itemLabel: (item) => _displayName(item.name),
              onChanged: (value) async {
                if (value == null || !mounted) return;
                final token = _beginLoad();
                _safeSetState(() {
                  _selectedCity = value;
                  _selectedArea = null;
                  _selectedSubArea = null;
                  _areas = [];
                  _subAreas = [];
                });
                await _loadAreas(city: value, token: token);
                if (!_isLoadCurrent(token)) return;
                _notify(userConfirmed: false);
              },
            ),
            const SizedBox(height: 12),
          ],
          _dropdown<AreaListingItem>(
            context,
            label: 'areaLabel'.translate(context),
            value: _selectedArea,
            items: _areas,
            isLoading: _loadingAreas,
            enabled: !cityRequiredButMissing && _selectedCity != null,
            hint: cityRequiredButMissing || _selectedCity == null
                ? 'selectCityFirstHint'.translate(context)
                : null,
            itemLabel: (item) => _displayName(item.name),
            onChanged: (value) async {
              if (!mounted) return;
              final token = _beginLoad();
              _safeSetState(() {
                _selectedArea = value;
                _selectedSubArea = null;
                _subAreas = [];
              });
              if (value != null) {
                await _loadSubAreas(area: value, token: token);
              }
              if (!_isLoadCurrent(token)) return;
              _notify(userConfirmed: value != null);
            },
          ),
          const SizedBox(height: 12),
          _customNameSection(
            context,
            disabled: cityRequiredButMissing,
            isArea: true,
          ),
          const SizedBox(height: 12),
          _dropdown<AreaListingSubArea>(
            context,
            label: 'subAreaOptionalLbl'.translate(context),
            value: _selectedSubArea,
            items: _subAreas,
            isLoading: _loadingSubAreas,
            enabled:
                !cityRequiredButMissing &&
                _selectedArea != null &&
                _subAreas.isNotEmpty,
            hint: _selectedArea == null
                ? 'selectAreaFirstHint'.translate(context)
                : null,
            itemLabel: (item) => _displayName(item.name),
            onChanged: (value) {
              if (!mounted) return;
              _safeSetState(() => _selectedSubArea = value);
              _notify(userConfirmed: _selectedArea != null);
            },
          ),
          const SizedBox(height: 12),
          _customNameSection(
            context,
            disabled: cityRequiredButMissing,
            isArea: false,
          ),
        ],
      ),
    );
  }

  Widget _customNameSection(
    BuildContext context, {
    required bool disabled,
    required bool isArea,
  }) {
    final hasArea =
        _selectedArea != null ||
        (widget.initialSelection?.suggestedAreaLabel ?? '').isNotEmpty;
    final sectionDisabled = disabled || (!isArea && !hasArea);
    final controller =
        isArea ? _customAreaController : _customSubAreaController;
    final labelKey = isArea ? 'newAreaNameLbl' : 'newSubAreaNameLbl';
    final hintKey = isArea
        ? (_canManage ? 'areaCustomHintAgent' : 'areaCustomHintUser')
        : (_canManage ? 'subAreaCustomHintAgent' : 'subAreaCustomHintUser');
    final btnKey = isArea
        ? (_canManage ? 'addAreaBtn' : 'suggestAreaBtn')
        : (_canManage ? 'addSubAreaBtn' : 'suggestSubAreaBtn');
    final inProgress = isArea ? _savingArea : _savingSubArea;
    final onPressed =
        isArea ? _addAreaFromCustomField : _addSubAreaFromCustomField;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _sectionLabel(context, labelKey.translate(context)),
        CustomTextFormField(
          controller: controller,
          hintText: hintKey.translate(context),
          isReadOnly: sectionDisabled,
        ),
        const SizedBox(height: 10),
        UiUtils.buildButton(
          context,
          height: 46,
          fontSize: context.font.sm,
          onPressed: onPressed,
          disabled: sectionDisabled || inProgress,
          buttonTitle: btnKey.translate(context),
          isInProgress: inProgress,
        ),
      ],
    );
  }

  Widget _dropdown<T>(
    BuildContext context, {
    required String label,
    required T? value,
    required List<T> items,
    required bool isLoading,
    required String Function(T item) itemLabel,
    required ValueChanged<T?> onChanged,
    bool enabled = true,
    String? hint,
  }) {
    final fieldStyle = TextStyle(
      color: context.color.textColorDark,
      fontSize: context.font.md.rf(context),
      fontWeight: FontWeight.w400,
      height: 1.25,
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _sectionLabel(context, label),
        DropdownButtonFormField<T>(
          isExpanded: true,
          value: items.contains(value) ? value : null,
          style: fieldStyle,
          dropdownColor: context.color.secondaryColor,
          iconEnabledColor: context.color.textColorDark,
          iconDisabledColor: context.color.textLightColor,
          hint: hint != null
              ? Text(
                  hint,
                  style: fieldStyle.copyWith(
                    color: context.color.textColorDark.withValues(alpha: 0.7),
                  ),
                )
              : null,
          items: [
            DropdownMenuItem<T>(
              value: null,
              child: Text(
                'clearSelectionLbl'.translate(context),
                style: fieldStyle.copyWith(
                  color: context.color.textLightColor,
                ),
              ),
            ),
            ...items.map(
              (item) => DropdownMenuItem<T>(
                value: item,
                child: Text(
                  itemLabel(item),
                  overflow: TextOverflow.ellipsis,
                  style: fieldStyle,
                ),
              ),
            ),
          ],
          selectedItemBuilder: (context) {
            return [
              Text(
                'clearSelectionLbl'.translate(context),
                style: fieldStyle.copyWith(
                  color: context.color.textLightColor,
                ),
                overflow: TextOverflow.ellipsis,
              ),
              ...items.map(
                (item) => Text(
                  itemLabel(item),
                  style: fieldStyle,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ];
          },
          onChanged: enabled && !isLoading ? onChanged : null,
          decoration: InputDecoration(
            isDense: true,
            contentPadding: const EdgeInsetsDirectional.only(
              start: 12,
              end: 8,
              top: 14,
              bottom: 14,
            ),
            filled: true,
            fillColor: context.color.secondaryColor,
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(4),
              borderSide: BorderSide(color: context.color.borderColor),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(4),
              borderSide: BorderSide(color: context.color.tertiaryColor),
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(4),
              borderSide: BorderSide(color: context.color.borderColor),
            ),
            suffixIcon: isLoading
                ? Padding(
                    padding: const EdgeInsets.all(12),
                    child: SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: context.color.tertiaryColor,
                      ),
                    ),
                  )
                : null,
          ),
        ),
      ],
    );
  }
}

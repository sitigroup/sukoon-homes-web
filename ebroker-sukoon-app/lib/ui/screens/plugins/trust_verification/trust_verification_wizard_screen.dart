import 'package:ebroker/data/model/trust_verification_models.dart';
import 'package:ebroker/data/repositories/trust_verification_repository.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/plugins/trust_verification/trust_verification_order_detail_screen.dart';
import 'package:ebroker/ui/screens/widgets/custom_text_form_field.dart';
import 'package:flutter/material.dart';

/// Native Sukoon Trust Verification order wizard (tenant / owner).
class TrustVerificationWizardScreen extends StatefulWidget {
  const TrustVerificationWizardScreen({super.key});

  static Route<dynamic> route(RouteSettings settings) {
    return MaterialPageRoute<void>(
      builder: (_) => const TrustVerificationWizardScreen(),
      settings: settings,
    );
  }

  @override
  State<TrustVerificationWizardScreen> createState() =>
      _TrustVerificationWizardScreenState();
}

class _TrustVerificationWizardScreenState
    extends State<TrustVerificationWizardScreen> {
  final TrustVerificationRepository _repository = TrustVerificationRepository();
  final _formKey = GlobalKey<FormState>();

  int _step = 0;
  bool _loading = true;
  String? _error;
  String _orderType = 'tenant';
  List<TrustVerificationCity> _cities = [];
  List<TrustVerificationPackage> _packages = [];
  TrustVerificationCity? _selectedCity;
  TrustVerificationPackage? _selectedPackage;

  final _fullName = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _currentAddress = TextEditingController();
  final _idNumber = TextEditingController();
  bool _consent = false;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    unawaited(_loadCities());
    _prefillFromProfile();
  }

  void _prefillFromProfile() {
    _fullName.text = HiveUtils.getUserDetails().name ?? '';
    _phone.text = HiveUtils.getUserDetails().mobile ?? '';
    _email.text = HiveUtils.getUserDetails().email ?? '';
  }

  @override
  void dispose() {
    _fullName.dispose();
    _phone.dispose();
    _email.dispose();
    _currentAddress.dispose();
    _idNumber.dispose();
    super.dispose();
  }

  Future<void> _loadCities() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final cities = await _repository.fetchCities();
      if (!mounted) return;
      setState(() {
        _cities = cities;
        _selectedCity = cities.isNotEmpty ? cities.first : null;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _loadPackages() async {
    final city = _selectedCity;
    if (city == null) return;
    setState(() {
      _loading = true;
      _error = null;
      _packages = [];
      _selectedPackage = null;
    });
    try {
      final packages = await _repository.fetchPackages(
        type: _orderType,
        citySlug: city.slug,
      );
      if (!mounted) return;
      setState(() {
        _packages = packages;
        _selectedPackage = packages.isNotEmpty ? packages.first : null;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (!_consent) {
      HelperUtils.showSnackBarMessage(
        context,
        'Please accept consent to continue',
      );
      return;
    }
    final package = _selectedPackage;
    final city = _selectedCity;
    if (package == null || city == null) return;

    setState(() => _submitting = true);
    try {
      final order = await _repository.submitOrder({
        'package_id': package.id,
        'city_slug': city.slug,
        'requester_name': _fullName.text.trim(),
        'requester_phone': _phone.text.trim(),
        'requester_email': _email.text.trim(),
        'subject': {
          'full_name': _fullName.text.trim(),
          'phone': _phone.text.trim(),
          'email': _email.text.trim(),
          'current_address': _currentAddress.text.trim(),
          'id_type': 'Aadhaar',
          'id_number': _idNumber.text.trim(),
          'consent_given': true,
        },
      });
      if (!mounted) return;
      await Navigator.pushReplacement<void, void>(
        context,
        TrustVerificationOrderDetailScreen.route(
          orderId: order.id!,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      HelperUtils.showSnackBarMessage(context, e.toString());
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.color.backgroundColor,
      appBar: CustomAppBar(title: 'Start verification'),
      body: _loading && _step < 2
          ? Center(child: UiUtils.progress())
          : _error != null && _packages.isEmpty && _step == 1
              ? _errorView()
              : Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: List.generate(3, (i) {
                          return Expanded(
                            child: Container(
                              height: 4,
                              margin: EdgeInsets.only(right: i < 2 ? 8 : 0),
                              decoration: BoxDecoration(
                                color: i <= _step
                                    ? context.color.tertiaryColor
                                    : context.color.borderColor,
                                borderRadius: BorderRadius.circular(2),
                              ),
                            ),
                          );
                        }),
                      ),
                    ),
                    Expanded(
                      child: SingleChildScrollView(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: _step == 0
                            ? _buildTypeAndCity()
                            : _step == 1
                                ? _buildPackages()
                                : _buildSubjectForm(),
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          if (_step > 0)
                            Expanded(
                              child: UiUtils.buildButton(
                                context,
                                onPressed: _submitting
                                    ? () {}
                                    : () => setState(() => _step--),
                                height: 48,
                                buttonTitle: 'Back',
                                buttonColor: context.color.secondaryColor,
                                textColor: context.color.textColorDark,
                              ),
                            ),
                          if (_step > 0) const SizedBox(width: 12),
                          Expanded(
                            flex: 2,
                            child: UiUtils.buildButton(
                              context,
                              onPressed: _submitting ? () {} : _onNext,
                              height: 48,
                              buttonTitle: _step == 2
                                  ? (_submitting ? 'Submitting…' : 'Submit order')
                                  : 'Continue',
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
    );
  }

  Widget _errorView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            UiUtils.buildButton(
              context,
              onPressed: _step == 1 ? _loadPackages : _loadCities,
              height: 44,
              buttonTitle: 'Retry',
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTypeAndCity() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Verification type',
          style: TextStyle(
            fontWeight: FontWeight.w600,
            color: context.color.textColorDark,
          ),
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(child: _typeChip('tenant', 'Tenant')),
            const SizedBox(width: 8),
            Expanded(child: _typeChip('owner', 'Owner')),
          ],
        ),
        const SizedBox(height: 20),
        Text(
          'City',
          style: TextStyle(
            fontWeight: FontWeight.w600,
            color: context.color.textColorDark,
          ),
        ),
        const SizedBox(height: 8),
        if (_cities.isEmpty)
          Text(
            'No cities available. Contact support.',
            style: TextStyle(color: context.color.textLightColor),
          )
        else
          DropdownButtonFormField<TrustVerificationCity>(
            value: _selectedCity,
            decoration: InputDecoration(
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            items: _cities
                .map(
                  (c) => DropdownMenuItem(
                    value: c,
                    child: Text(c.label),
                  ),
                )
                .toList(),
            onChanged: (v) => setState(() => _selectedCity = v),
          ),
      ],
    );
  }

  Widget _typeChip(String type, String label) {
    final selected = _orderType == type;
    return InkWell(
      onTap: () => setState(() => _orderType = type),
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: selected
              ? context.color.tertiaryColor.withValues(alpha: 0.15)
              : context.color.secondaryColor,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: selected
                ? context.color.tertiaryColor
                : context.color.borderColor,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontWeight: FontWeight.w600,
            color: context.color.textColorDark,
          ),
        ),
      ),
    );
  }

  Widget _buildPackages() {
    if (_packages.isEmpty) {
      return Text(
        'No packages for this city. Try another city or type.',
        style: TextStyle(color: context.color.textLightColor),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: _packages.map((pkg) {
        final selected = _selectedPackage?.id == pkg.id;
        return Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: InkWell(
            onTap: () => setState(() => _selectedPackage = pkg),
            borderRadius: BorderRadius.circular(8),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: context.color.secondaryColor,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: selected
                      ? context.color.tertiaryColor
                      : context.color.borderColor,
                  width: selected ? 2 : 1,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    pkg.name,
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: context.font.lg,
                      color: context.color.textColorDark,
                    ),
                  ),
                  if (pkg.price != null)
                    Text(
                      '₹${pkg.price!.toStringAsFixed(0)}',
                      style: TextStyle(
                        color: context.color.tertiaryColor,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  if (pkg.description != null && pkg.description!.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(top: 6),
                      child: Text(
                        pkg.description!,
                        style: TextStyle(
                          color: context.color.textLightColor,
                          height: 1.35,
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _buildSubjectForm() {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CustomTextFormField(
            controller: _fullName,
            hintText: 'Full name',
            validator: CustomTextFieldValidator.nullCheck,
          ),
          const SizedBox(height: 12),
          CustomTextFormField(
            controller: _phone,
            hintText: 'Mobile number',
            keyboard: TextInputType.phone,
            validator: CustomTextFieldValidator.phoneNumber,
          ),
          const SizedBox(height: 12),
          CustomTextFormField(
            controller: _email,
            hintText: 'Email (optional)',
            keyboard: TextInputType.emailAddress,
          ),
          const SizedBox(height: 12),
          CustomTextFormField(
            controller: _currentAddress,
            hintText: 'Current address',
            maxLine: 3,
          ),
          const SizedBox(height: 12),
          CustomTextFormField(
            controller: _idNumber,
            hintText: 'ID number (Aadhaar)',
          ),
          const SizedBox(height: 16),
          CheckboxListTile(
            value: _consent,
            onChanged: (v) => setState(() => _consent = v ?? false),
            title: Text(
              'I consent to Sukoon verification checks on the details provided.',
              style: TextStyle(
                fontSize: context.font.sm,
                color: context.color.textColorDark,
              ),
            ),
            controlAffinity: ListTileControlAffinity.leading,
            contentPadding: EdgeInsets.zero,
          ),
        ],
      ),
    );
  }

  Future<void> _onNext() async {
    if (_step == 0) {
      if (_selectedCity == null) {
        HelperUtils.showSnackBarMessage(context, 'Select a city');
        return;
      }
      setState(() => _step = 1);
      await _loadPackages();
      return;
    }
    if (_step == 1) {
      if (_selectedPackage == null) {
        HelperUtils.showSnackBarMessage(context, 'Select a package');
        return;
      }
      setState(() => _step = 2);
      return;
    }
    await _submit();
  }
}

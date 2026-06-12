import 'package:dio/dio.dart';
import 'package:ebroker/data/cubits/agents/apply_agent_verification_cubit.dart';
import 'package:ebroker/data/cubits/agents/fetch_agent_registration_form_cubit.dart';
import 'package:ebroker/data/cubits/agents/fetch_agent_verification_form_values.dart';
import 'package:ebroker/data/model/agent/agent_registration_form_section_model.dart';
import 'package:ebroker/data/model/agent/agent_verification_form_values_model.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/profile/widgets/user_verification_form.dart'
    show AgentDocuments;
import 'package:flutter/material.dart';

class VerifyAgentFormScreen extends StatelessWidget {
  const VerifyAgentFormScreen({super.key});

  static Route<dynamic> route(RouteSettings routeSettings) {
    return CupertinoPageRoute(
      builder: (_) => MultiBlocProvider(
        providers: [
          BlocProvider(
            create: (_) => FetchAgentRegistrationFormCubit(),
          ),
          BlocProvider(
            create: (_) => FetchAgentVerificationFormValuesCubit(),
          ),
          BlocProvider(
            create: (_) => ApplyAgentVerificationCubit(),
          ),
        ],
        child: const _VerifyAgentFormBody(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return const _VerifyAgentFormBody();
  }
}

class _VerifyAgentFormBody extends StatefulWidget {
  const _VerifyAgentFormBody();

  @override
  State<_VerifyAgentFormBody> createState() => _VerifyAgentFormBodyState();
}

class _VerifyAgentFormBodyState extends State<_VerifyAgentFormBody> {
  static const _formType = 'verify_agent';

  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _controllers = {};
  final Map<String, dynamic> _formData = {};
  final Map<int, AgentDocuments> _selectedDocuments = {};
  int _currentStep = 0;
  bool _isInitialized = false;

  @override
  void initState() {
    super.initState();
    unawaited(
      context
          .read<FetchAgentRegistrationFormCubit>()
          .fetchAgentRegistrationForm(formType: _formType),
    );
    unawaited(
      context
          .read<FetchAgentVerificationFormValuesCubit>()
          .fetchAgentsVerificationFormValues(
            forceRefresh: true,
            formType: _formType,
          ),
    );
  }

  @override
  void dispose() {
    for (final controller in _controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  void _initializeFormData(List<AgentVerificationFormValueModel> values) {
    if (_isInitialized) return;
    _isInitialized = true;

    if (values.isEmpty) return;
    final customerValues = values.first.verifyCustomerValues;
    if (customerValues == null) return;

    for (final entry in customerValues) {
      final form = entry.verifyForm;
      if (form == null) continue;

      final fieldType = form.fieldType ?? '';
      final fieldId = form.id.toString();
      final fieldValue = entry.value?.toString() ?? '';

      if (fieldValue.isEmpty) continue;

      switch (fieldType) {
        case 'text':
        case 'number':
        case 'textarea':
          _controllers[fieldId] = TextEditingController(text: fieldValue);
          _formData[fieldId] = fieldValue;
        case 'radio':
        case 'dropdown':
          _formData[fieldId] = fieldValue;
        case 'checkbox':
          _formData[fieldId] = fieldValue; // comma-separated string
        case 'file':
          if (form.id != null) {
            _selectedDocuments[form.id!] = AgentDocuments(
              name: fieldValue,
              isExisting: true,
            );
          }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.color.backgroundColor,
      appBar: CustomAppBar(
        title: 'verifyAgent'.translate(context),
      ),
      body:
          BlocBuilder<
            FetchAgentRegistrationFormCubit,
            FetchAgentRegistrationFormState
          >(
            builder: (context, sectionsState) {
              return BlocBuilder<
                FetchAgentVerificationFormValuesCubit,
                FetchAgentVerificationFormValuesState
              >(
                builder: (context, valuesState) {
                  final isLoading =
                      sectionsState is FetchAgentRegistrationFormLoading ||
                      valuesState is FetchAgentVerificationFormValuesLoading;

                  if (isLoading) {
                    return Center(child: UiUtils.progress());
                  }

                  if (sectionsState is FetchAgentRegistrationFormFailure) {
                    return SomethingWentWrong(
                      errorMessage: sectionsState.errorMessage.toString(),
                    );
                  }

                  if (sectionsState is FetchAgentRegistrationFormSuccess) {
                    final sections = sectionsState.sections;

                    // Pre-fill once both states are ready
                    if (valuesState
                        is FetchAgentVerificationFormValuesSuccess) {
                      _initializeFormData(valuesState.values);
                    }

                    if (sections.isEmpty) {
                      return Center(
                        child: CustomText('noDataFound'.translate(context)),
                      );
                    }

                    return Column(
                      children: [
                        _buildStepper(context, sections),
                        Expanded(
                          child: SingleChildScrollView(
                            child: _buildStepContent(context, sections),
                          ),
                        ),
                        _buildBottomButtons(context, sections),
                      ],
                    );
                  }

                  return const SizedBox.shrink();
                },
              );
            },
          ),
    );
  }

  Widget _buildStepper(
    BuildContext context,
    List<AgentRegistrationFormSectionModel> sections,
  ) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      color: context.color.secondaryColor,
      padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
      child: Row(
        crossAxisAlignment: .start,
        children: List.generate(sections.length * 2 - 1, (index) {
          if (index.isOdd) {
            final stepIndex = index ~/ 2;
            final isCompleted = stepIndex < _currentStep;
            return Expanded(
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                height: 6.rh(context),
                margin: .symmetric(
                  vertical: 8.rh(context),
                ),
                decoration: BoxDecoration(
                  color: isCompleted
                      ? context.color.tertiaryColor
                      : context.color.borderColor,
                  borderRadius: BorderRadius.circular(100),
                ),
              ),
            );
          }
          final stepIndex = index ~/ 2;
          final isActive = stepIndex == _currentStep;
          final isCompleted = stepIndex < _currentStep;
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                width: 24.rw(context),
                height: 24.rh(context),
                padding: EdgeInsets.all(isCompleted || isActive ? 2 : 6),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: context.color.secondaryColor,
                  border: Border.all(
                    color: isActive || isCompleted
                        ? context.color.tertiaryColor
                        : context.color.borderColor,
                    width: 2,
                  ),
                ),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  decoration: BoxDecoration(
                    color: isActive || isCompleted
                        ? context.color.tertiaryColor
                        : context.color.borderColor,
                    shape: .circle,
                  ),
                ),
              ),
              CustomText(
                sections[stepIndex].translatedName,
                color: context.color.textColorDark,
              ),
            ],
          );
        }),
      ),
    );
  }

  Widget _buildStepContent(
    BuildContext context,
    List<AgentRegistrationFormSectionModel> sections,
  ) {
    final section = sections[_currentStep];
    return Padding(
      padding: const EdgeInsets.all(18),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: section.fields.map(_buildFormField).toList(),
        ),
      ),
    );
  }

  Widget _buildFormField(AgentRegistrationFormFieldModel field) {
    switch (field.fieldType) {
      case 'text':
        return _buildTextField(field);
      case 'number':
        return _buildTextField(field);
      case 'radio':
        return _buildRadioGroup(field);
      case 'checkbox':
        return _buildCheckboxGroup(field);
      case 'dropdown':
        return _buildDropdown(field);
      case 'textarea':
        return _buildTextArea(field);
      case 'file':
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CustomText(
              field.translatedName,
              fontSize: context.font.sm,
              fontWeight: FontWeight.w500,
            ),
            const SizedBox(height: 4),
            _VerifyAgentDocumentPicker(
              initialDocument: _selectedDocuments[field.id],
              onDocumentSelected: (doc) {
                setState(() {
                  if (doc != null) {
                    _selectedDocuments[field.id] = doc;
                  } else {
                    _selectedDocuments.remove(field.id);
                  }
                });
              },
            ),
            const SizedBox(height: 16),
          ],
        );
      default:
        return const SizedBox.shrink();
    }
  }

  Widget _buildTextField(AgentRegistrationFormFieldModel field) {
    final key = field.id.toString();
    if (!_controllers.containsKey(key)) {
      _controllers[key] = TextEditingController(
        text: _formData[key]?.toString() ?? '',
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CustomText(
          field.translatedName,
          fontSize: context.font.sm,
          fontWeight: FontWeight.w500,
        ),
        const SizedBox(height: 4),
        CustomTextFormField(
          hintText: '${'enter'.translate(context)} ${field.translatedName}',
          controller: _controllers[key],
          action: TextInputAction.next,
          validator: CustomTextFieldValidator.nullCheck,
          onChange: (value) => _formData[key] = value,
          keyboard: field.fieldType == 'number'
              ? TextInputType.number
              : TextInputType.text,
          formaters: field.fieldType == 'number'
              ? [FilteringTextInputFormatter.digitsOnly]
              : null,
        ),
        const SizedBox(height: 16),
      ],
    );
  }

  Widget _buildTextArea(AgentRegistrationFormFieldModel field) {
    final key = field.id.toString();
    if (!_controllers.containsKey(key)) {
      _controllers[key] = TextEditingController(
        text: _formData[key]?.toString() ?? '',
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CustomText(
          field.translatedName,
          fontSize: context.font.sm,
          fontWeight: FontWeight.w500,
        ),
        const SizedBox(height: 4),
        CustomTextFormField(
          hintText: '${'enter'.translate(context)} ${field.translatedName}',
          controller: _controllers[key],
          action: TextInputAction.newline,
          validator: CustomTextFieldValidator.nullCheck,
          onChange: (value) => _formData[key] = value,
          maxLine: 5,
          minLine: 3,
        ),
        const SizedBox(height: 16),
      ],
    );
  }

  Widget _buildRadioGroup(AgentRegistrationFormFieldModel field) {
    final key = field.id.toString();
    final savedValue = _formData[key]?.toString().trim();
    final firstValue = field.formFieldsValues.first.value.trim();
    final initialValue = (savedValue != null && savedValue.isNotEmpty)
        ? savedValue
        : firstValue;
    return FormField<String>(
      key: ValueKey('radio_${field.id}'),
      initialValue: initialValue,
      validator: (value) {
        if (value == null || value.isEmpty) {
          return '${field.translatedName} ${'isRequired'.translate(context)}';
        }
        return null;
      },
      builder: (state) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CustomText(
              field.translatedName,
              fontSize: context.font.sm,
              fontWeight: FontWeight.w500,
            ),
            const SizedBox(height: 4),
            ...field.formFieldsValues.map(
              (option) => Container(
                margin: const EdgeInsets.only(bottom: 4),
                decoration: BoxDecoration(
                  border: Border.all(
                    color: state.hasError
                        ? Colors.red
                        : context.color.borderColor,
                  ),
                  color: context.color.secondaryColor,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: RadioGroup<String>(
                  groupValue: state.value,
                  onChanged: (value) {
                    state.didChange(value);
                    _formData[key] = value;
                  },
                  child: RadioListTile(
                    radioScaleFactor: 1.1,
                    dense: true,
                    activeColor: context.color.tertiaryColor,
                    controlAffinity: ListTileControlAffinity.trailing,
                    title: CustomText(
                      option.translatedValue ?? option.value,
                      fontSize: context.font.sm,
                      color: context.color.textLightColor,
                    ),
                    value: option.value,
                  ),
                ),
              ),
            ),
            if (state.hasError)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: 4, start: 12),
                child: CustomText(
                  state.errorText!,
                  color: context.color.error,
                  fontSize: context.font.xs,
                ),
              ),
            const SizedBox(height: 16),
          ],
        );
      },
    );
  }

  Widget _buildCheckboxGroup(AgentRegistrationFormFieldModel field) {
    final key = field.id.toString();
    final savedValue = _formData[key];
    var initialValues = <String>[];
    if (savedValue is String && savedValue.isNotEmpty) {
      initialValues = savedValue.split(',').map((e) => e.trim()).toList();
    } else if (savedValue is List<String>) {
      initialValues = savedValue;
    }
    return FormField<List<String>>(
      key: ValueKey('checkbox_${field.id}'),
      initialValue: initialValues,
      validator: (value) {
        if (value == null || value.isEmpty) {
          return '${field.translatedName} ${'isRequired'.translate(context)}';
        }
        return null;
      },
      builder: (state) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CustomText(
              field.translatedName,
              fontSize: context.font.sm,
              fontWeight: FontWeight.w500,
            ),
            const SizedBox(height: 4),
            ...field.formFieldsValues.map(
              (option) => Container(
                margin: const EdgeInsets.only(bottom: 4),
                decoration: BoxDecoration(
                  border: Border.all(
                    color: state.hasError
                        ? Colors.red
                        : context.color.borderColor,
                  ),
                  color: context.color.secondaryColor,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: CheckboxListTile(
                  dense: true,
                  activeColor: context.color.tertiaryColor,
                  title: CustomText(
                    option.translatedValue ?? option.value,
                    fontSize: context.font.sm,
                    fontWeight: FontWeight.w400,
                    color: context.color.textLightColor,
                  ),
                  value: state.value!.contains(option.value),
                  onChanged: (checked) {
                    final newValue = List<String>.from(state.value!);
                    if (checked!) {
                      newValue.add(option.value);
                    } else {
                      newValue.remove(option.value);
                    }
                    state.didChange(newValue);
                    _formData[key] = newValue.join(',');
                  },
                ),
              ),
            ),
            if (state.hasError)
              Padding(
                padding: const EdgeInsets.only(top: 4, left: 12),
                child: CustomText(
                  state.errorText!,
                  color: context.color.error,
                  fontSize: context.font.xs,
                ),
              ),
            const SizedBox(height: 16),
          ],
        );
      },
    );
  }

  Widget _buildDropdown(AgentRegistrationFormFieldModel field) {
    if (field.formFieldsValues.isEmpty) return const SizedBox.shrink();
    final key = field.id.toString();
    // Use saved value keyed by field.id (unique) to avoid cross-section contamination
    final savedValue = _formData[key]?.toString().trim();
    final firstValue = field.formFieldsValues.first.value.trim();
    final initialValue = (savedValue != null && savedValue.isNotEmpty)
        ? savedValue
        : firstValue;
    return FormField<String>(
      key: ValueKey('dropdown_${field.id}'),
      initialValue: initialValue,
      validator: (value) {
        if (value == null || value.isEmpty) {
          return '${field.translatedName} ${'isRequired'.translate(context)}';
        }
        return null;
      },
      builder: (state) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CustomText(
              field.translatedName,
              fontSize: context.font.sm,
              fontWeight: FontWeight.w500,
            ),
            const SizedBox(height: 4),
            DropdownButtonHideUnderline(
              child: Container(
                width: context.screenWidth,
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  border: Border.all(
                    color: state.hasError
                        ? Colors.red
                        : context.color.borderColor,
                  ),
                  color: context.color.secondaryColor,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Builder(
                  builder: (context) {
                    // Deduplicate by trimmed value to handle API inconsistencies
                    final uniqueOptions = {
                      for (final option in field.formFieldsValues)
                        option.value.trim(): option,
                    }.values.toList();
                    final trimmedState = state.value?.trim();
                    final safeValue =
                        uniqueOptions.any(
                          (o) => o.value.trim() == trimmedState,
                        )
                        ? trimmedState
                        : null;
                    return DropdownButton<String>(
                      isDense: true,
                      icon: Icon(
                        Icons.keyboard_arrow_down,
                        size: 24.rh(context),
                      ),
                      padding: const EdgeInsets.all(4),
                      borderRadius: BorderRadius.circular(4),
                      elevation: 1,
                      dropdownColor: context.color.secondaryColor,
                      isExpanded: true,
                      value: safeValue,
                      items: uniqueOptions.map((option) {
                        return DropdownMenuItem<String>(
                          value: option.value.trim(),
                          child: CustomText(
                            option.translatedValue ?? option.value,
                            fontSize: context.font.xs,
                            color: context.color.textLightColor,
                          ),
                        );
                      }).toList(),
                      onChanged: (value) {
                        state.didChange(value);
                        _formData[key] = value;
                      },
                    );
                  },
                ),
              ),
            ),
            if (state.hasError)
              Padding(
                padding: const EdgeInsets.only(top: 5, left: 12),
                child: CustomText(
                  state.errorText!,
                  color: context.color.error,
                  fontSize: context.font.xs,
                ),
              ),
            const SizedBox(height: 16),
          ],
        );
      },
    );
  }

  Widget _buildBottomButtons(
    BuildContext context,
    List<AgentRegistrationFormSectionModel> sections,
  ) {
    final isLastStep = _currentStep == sections.length - 1;
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
      color: context.color.secondaryColor,
      child: BlocConsumer<ApplyAgentVerificationCubit, ApplyAgentVerificationState>(
        listener: (context, state) {
          if (state is ApplyAgentVerificationSuccess) {
            unawaited(
              Navigator.pushReplacementNamed(
                context,
                Routes.agentRegistrationSuccess,
              ),
            );
          } else if (state is ApplyAgentVerificationFailure) {
            HelperUtils.showSnackBarMessage(
              context,
              '${'failedTOApplyVerification'.translate(context)}: ${state.errorMessage.translate(context)}',
            );
          }
        },
        builder: (context, state) {
          final isLoading = state is ApplyAgentVerificationInProgress;
          return Row(
            children: [
              if (_currentStep > 0) ...[
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => setState(() => _currentStep--),
                    style: OutlinedButton.styleFrom(
                      side: BorderSide(color: context.color.tertiaryColor),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    child: CustomText(
                      'back'.translate(context),
                      color: context.color.tertiaryColor,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
              ],
              Expanded(
                child: ElevatedButton(
                  onPressed: isLoading
                      ? null
                      : () async {
                          if (_formKey.currentState!.validate()) {
                            if (isLastStep) {
                              await _submitForm(sections);
                            } else {
                              setState(() => _currentStep++);
                            }
                          }
                        },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: context.color.tertiaryColor,
                    foregroundColor: context.color.buttonColor,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  child: isLoading
                      ? SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: context.color.buttonColor,
                          ),
                        )
                      : CustomText(
                          isLastStep
                              ? 'submit'.translate(context)
                              : 'next'.translate(context),
                          color: context.color.buttonColor,
                          fontWeight: FontWeight.w600,
                        ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _submitForm(
    List<AgentRegistrationFormSectionModel> sections,
  ) async {
    final formFields = <Map<String, dynamic>>[];
    for (final section in sections) {
      for (final field in section.fields) {
        if (field.fieldType == 'file') {
          final doc = _selectedDocuments[field.id];
          if (doc == null) {
            HelperUtils.showSnackBarMessage(
              context,
              'pleaseSelectAValidDocument',
            );
            return;
          }
          if (doc.isExisting && doc.file == null) {
            // Existing unmodified file: send the name reference
            formFields.add({
              'id': field.id.toString(),
              'value': doc.name,
            });
            continue;
          }
          if (doc.file != null) {
            formFields.add({
              'id': field.id.toString(),
              'value': MultipartFile.fromFileSync(
                doc.file!,
                filename: doc.name,
              ),
            });
          }
        } else if (field.fieldType == 'checkbox') {
          final value = _formData[field.id.toString()];
          if (value != null && value.toString().isNotEmpty) {
            formFields.add({
              'id': field.id.toString(),
              'value': value.toString(),
            });
          }
        } else {
          final value = _formData[field.id.toString()];
          if (value != null) {
            formFields.add({
              'id': field.id.toString(),
              'value': value.toString(),
            });
          }
        }
      }
    }
    await context.read<ApplyAgentVerificationCubit>().applyVerification(
      parameters: {'form_fields': formFields, 'form_type': _formType},
    );
  }
}

class _VerifyAgentDocumentPicker extends StatefulWidget {
  const _VerifyAgentDocumentPicker({
    required this.onDocumentSelected,
    this.initialDocument,
  });

  final void Function(AgentDocuments?) onDocumentSelected;
  final AgentDocuments? initialDocument;

  @override
  State<_VerifyAgentDocumentPicker> createState() =>
      _VerifyAgentDocumentPickerState();
}

class _VerifyAgentDocumentPickerState
    extends State<_VerifyAgentDocumentPicker> {
  AgentDocuments? selectedDocument;

  @override
  void initState() {
    super.initState();
    selectedDocument = widget.initialDocument;
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        DottedBorder(
          options: RoundedRectDottedBorderOptions(
            color: context.color.textLightColor,
            radius: const Radius.circular(4),
          ),
          child: SizedBox(
            width: 48.rh(context),
            height: 48.rw(context),
            child: IconButton(
              onPressed: _pickDocument,
              icon: Icon(
                Icons.upload,
                color: context.color.textLightColor,
              ),
            ),
          ),
        ),
        const SizedBox(width: 16),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CustomText('UploadDocs'.translate(context)),
            const SizedBox(height: 4),
            CustomText(
              selectedDocument != null
                  ? (selectedDocument!.isExisting
                        ? selectedDocument!.name
                        : 'oneFileSelected'.translate(context))
                  : 'noFileSelected'.translate(context),
              color: context.color.textLightColor,
              fontSize: context.font.xs,
            ),
          ],
        ),
      ],
    );
  }

  Future<void> _pickDocument() async {
    try {
      final result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: [
          'pdf',
          'doc',
          'docx',
          'jpg',
          'jpeg',
          'png',
          'webp',
        ],
      );
      if (result != null && result.files.isNotEmpty) {
        final file = result.files.first;
        setState(() {
          selectedDocument = AgentDocuments(name: file.name, file: file.path);
        });
        widget.onDocumentSelected(selectedDocument);
      }
    } on Exception catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    }
  }
}

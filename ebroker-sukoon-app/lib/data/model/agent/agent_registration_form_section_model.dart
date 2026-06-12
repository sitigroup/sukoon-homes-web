// Field option value for the become-agent form.
// Uses `agent_verification_form_id` key (not `verify_customer_form_id`).
class AgentRegistrationFieldValue {
  AgentRegistrationFieldValue({
    required this.id,
    required this.agentVerificationFormId,
    required this.value,
    required this.translatedValue,
  });

  factory AgentRegistrationFieldValue.fromJson(Map<String, dynamic> json) {
    return AgentRegistrationFieldValue(
      id: json['id'] as int? ?? 0,
      agentVerificationFormId:
          json['agent_verification_form_id'] as int? ?? 0,
      value: json['value']?.toString() ?? '',
      translatedValue: json['translated_value']?.toString() ?? '',
    );
  }

  final int id;
  final int agentVerificationFormId;
  final String value;
  final String? translatedValue;
}

class AgentRegistrationFormFieldModel {
  AgentRegistrationFormFieldModel({
    required this.id,
    required this.agentVerificationFormSectionId,
    required this.name,
    required this.fieldType,
    required this.sequence,
    required this.translatedName,
    required this.formFieldsValues,
  });

  factory AgentRegistrationFormFieldModel.fromJson(
    Map<String, dynamic> json,
  ) {
    return AgentRegistrationFormFieldModel(
      id: json['id'] as int,
      agentVerificationFormSectionId:
          json['agent_verification_form_section_id'] as int,
      name: json['name']?.toString() ?? '',
      fieldType: json['field_type']?.toString() ?? '',
      sequence: json['sequence'] as int? ?? 0,
      translatedName: json['translated_name']?.toString() ?? '',
      formFieldsValues: List<AgentRegistrationFieldValue>.from(
        (json['form_fields_values'] as List).map(
          (x) =>
              AgentRegistrationFieldValue.fromJson(x as Map<String, dynamic>),
        ),
      ),
    );
  }

  final int id;
  final int agentVerificationFormSectionId;
  final String name;
  final String fieldType;
  final int sequence;
  final String translatedName;
  final List<AgentRegistrationFieldValue> formFieldsValues;
}

class AgentRegistrationFormSectionModel {
  AgentRegistrationFormSectionModel({
    required this.id,
    required this.name,
    required this.formType,
    required this.sequence,
    required this.translatedName,
    required this.fields,
  });

  factory AgentRegistrationFormSectionModel.fromJson(
    Map<String, dynamic> json,
  ) {
    return AgentRegistrationFormSectionModel(
      id: json['id'] as int,
      name: json['name']?.toString() ?? '',
      formType: json['form_type']?.toString() ?? '',
      sequence: json['sequence'] as int? ?? 0,
      translatedName: json['translated_name']?.toString() ?? '',
      fields: List<AgentRegistrationFormFieldModel>.from(
        (json['agent_verification_forms'] as List).map(
          (x) => AgentRegistrationFormFieldModel.fromJson(
            x as Map<String, dynamic>,
          ),
        ),
      ),
    );
  }

  final int id;
  final String name;
  final String formType;
  final int sequence;
  final String translatedName;
  final List<AgentRegistrationFormFieldModel> fields;
}

import 'package:ebroker/data/model/agent/agent_model.dart';
import 'package:ebroker/data/model/agent/agent_registration_form_section_model.dart';
import 'package:ebroker/data/model/agent/agent_verification_form_fields_model.dart';
import 'package:ebroker/data/model/agent/agent_verification_form_values_model.dart';
import 'package:ebroker/data/model/agent/agents_property_model.dart';
import 'package:ebroker/data/model/data_output.dart';
import 'package:ebroker/utils/api.dart';
import 'package:ebroker/utils/constant.dart';

class AgentsRepository {
  Future<DataOutput<AgentModel>> fetchAllAgents({
    required int offset,
  }) async {
    final response = await Api.get(
      url: Api.getAgents,
      queryParameters: {
        Api.limit: Constant.loadLimit,
        Api.offset: offset,
      },
    );
    final modelList = (response['data'] as List)
        .map<AgentModel>(
          (e) => AgentModel.fromJson(Map.from(e as Map? ?? {})),
        )
        .toList();
    return DataOutput(
      total: int.parse(response['total']?.toString() ?? '0'),
      modelList: modelList,
    );
  }

  Future<({String agentId, bool isAdmin})> fetchBySlug(String slug) async {
    try {
      final result = await Api.get(
        url: Api.getAgentProperties,
        queryParameters: {'slug_id': slug},
      );

      final data = result['data'];
      if (data is Map<String, dynamic>) {
        final customerData = data['customer_data'];
        if (customerData is Map<String, dynamic>) {
          final agentId = customerData['id']?.toString() ?? '';
          final isAdmin = customerData['is_admin'] as bool? ?? false;

          if (agentId.isEmpty) {
            throw Exception('Agent ID not found in response');
          }

          return (agentId: agentId, isAdmin: isAdmin);
        }
      }

      throw Exception('Invalid data format received');
    } on Exception catch (e) {
      throw Exception('Error fetching agent by slug: $e');
    }
  }

  Future<({int total, AgentPropertyProjectModel agentsProperty})>
  fetchAgentProperties({
    required int offset,
    required String agentId,
    required bool isAdmin,
    int? limit,
  }) async {
    try {
      final parameters = <String, dynamic>{
        Api.offset: offset,
        Api.limit: limit ?? Constant.loadLimit,
        Api.id: agentId,
        if (isAdmin) 'is_admin': '1',
      };

      final result = await Api.get(
        url: Api.getAgentProperties,
        queryParameters: parameters,
      );

      if (result['data'] == null) {
        throw Exception('No data found');
      }
      final data = result['data'] as Map<String, dynamic>;

      final agentsProperty = AgentPropertyProjectModel.fromJson(data);
      final total = result['total'] as int? ?? 0;

      return (
        total: total,
        agentsProperty: agentsProperty,
      );
    } on Exception catch (e) {
      throw Exception('Error fetching agent properties: $e');
    }
  }

  Future<({int total, AgentPropertyProjectModel agentsProperty})>
  fetchAgentProjects({
    required String agentId,
    required int offset,
    required int isProjects,
    required bool isAdmin,
  }) async {
    final parameters = <String, dynamic>{
      Api.offset: offset,
      Api.limit: Constant.loadLimit,
      Api.isProjects: isProjects,
      Api.id: agentId,
      if (isAdmin) 'is_admin': '1',
    };

    final result = await Api.get(
      url: Api.getAgentProperties,
      queryParameters: parameters,
    );
    final data = result['data'] as Map<String, dynamic>;
    final total = result['total'] as int;

    return (
      total: total,
      agentsProperty: AgentPropertyProjectModel.fromJson(data),
    );
  }

  Future<List<AgentVerificationFormFieldsModel>>
  getUserVerificationFormFields() async {
    try {
      final result = await Api.get(
        url: Api.getUserVerificationFormFields,
      );

      final modelList = (result['data'] as List)
          .cast<Map<String, dynamic>>()
          .map<AgentVerificationFormFieldsModel>(
            AgentVerificationFormFieldsModel.fromJson,
          )
          .toList();
      return modelList;
    } on Exception catch (e) {
      throw Exception('Error fetching agent verification form fields: $e');
    }
  }

  Future<List<AgentVerificationFormValueModel>> getAgentVerificationFormValues({
    String? formType,
  }) async {
    try {
      final result = await Api.get(
        url: Api.apiGetAgentVerificationFormValues,
        queryParameters: formType != null ? {'form_type': formType} : null,
      );

      if (result['data'] is Map<String, dynamic>) {
        final singleModel = AgentVerificationFormValueModel.fromJson(
          result['data'] as Map<String, dynamic>,
        );
        return [singleModel];
      } else if (result['data'] is List) {
        final modelList = (result['data'] as List)
            .cast<Map<String, dynamic>>()
            .map<AgentVerificationFormValueModel>(
              AgentVerificationFormValueModel.fromJson,
            )
            .toList();
        return modelList;
      } else {
        throw Exception('Unexpected data format in API response');
      }
    } on Exception catch (e) {
      throw Exception('Error fetching agent verification form values: $e');
    }
  }

  Future<Map<String, dynamic>> createAgentVerification({
    required Map<String, dynamic> parameters,
  }) async {
    const api = Api.apiGetApplyAgentVerification;

    return Api.post(url: api, parameter: parameters);
  }

  Future<List<AgentVerificationFormFieldsModel>>
  fetchUserVerificationFormFields() async {
    try {
      final result = await Api.get(
        url: Api.getUserVerificationForm,
      );
      if (result['data'] == null) {
        throw ApiException('nodatafound');
      }
      final modelList = (result['data'] as List)
          .cast<Map<String, dynamic>>()
          .map<AgentVerificationFormFieldsModel>(
            AgentVerificationFormFieldsModel.fromJson,
          )
          .toList();
      return modelList;
    } on Exception catch (e) {
      throw ApiException(e);
    }
  }

  Future<List<AgentVerificationFormValueModel>>
  getUserVerificationFormValues() async {
    try {
      final result = await Api.get(
        url: Api.getUserVerificationFormValues,
      );
      if (result['data'] is Map<String, dynamic>) {
        return [
          AgentVerificationFormValueModel.fromJson(
            result['data'] as Map<String, dynamic>,
          ),
        ];
      } else if (result['data'] is List) {
        return (result['data'] as List)
            .cast<Map<String, dynamic>>()
            .map<AgentVerificationFormValueModel>(
              AgentVerificationFormValueModel.fromJson,
            )
            .toList();
      } else {
        throw Exception('Unexpected data format in API response');
      }
    } on Exception catch (e) {
      throw Exception('Error fetching user verification form values: $e');
    }
  }

  Future<Map<String, dynamic>> applyUserVerification({
    required Map<String, dynamic> parameters,
  }) async {
    try {
      return Api.post(url: Api.applyUserVerification, parameter: parameters);
    } on Exception catch (e) {
      throw ApiException(e);
    }
  }

  Future<List<AgentRegistrationFormSectionModel>> getAgentRegistrationForm({
    required String formType,
  }) async {
    final result = await Api.get(
      url: Api.getAgentRegistrationForm,
      queryParameters: {'form_type': formType},
    );
    if (result['data'] == null) {
      throw ApiException(result['message'] ?? 'noDataFound');
    }
    return (result['data'] as List)
        .cast<Map<String, dynamic>>()
        .map(AgentRegistrationFormSectionModel.fromJson)
        .toList();
  }

  Future<({int total, AgentPropertyProjectModel agentsProperty})>
  searchAgentProperties({
    required String agentId,
    required bool isAdmin,
    required String searchQuery,
  }) async {
    final parameters = <String, dynamic>{
      'search': searchQuery,
      Api.id: agentId,
      if (isAdmin) 'is_admin': '1',
    };
    final result = await Api.get(
      url: Api.getAgentProperties,
      queryParameters: parameters,
    );
    final data = result['data'] as Map<String, dynamic>;
    final total = result['total'] as int;

    return (
      total: total,
      agentsProperty: AgentPropertyProjectModel.fromJson(data),
    );
  }
}

import 'package:ebroker/data/model/custom_page_model.dart';
import 'package:ebroker/data/model/data_output.dart';
import 'package:ebroker/utils/api.dart';
import 'package:ebroker/utils/constant.dart';

class CustomPagesRepository {
  Future<DataOutput<CustomPageModel>> fetchCustomPages({
    required int offset,
  }) async {
    final result = await Api.get(
      url: Api.apiGetCustomPages,
      useAuthToken: false,
      queryParameters: {
        Api.limit: Constant.loadLimit,
        Api.offset: offset,
      },
    );
    final modelList = (result['data'] as List? ?? [])
        .cast<Map<String, dynamic>>()
        .map<CustomPageModel>(CustomPageModel.fromJson)
        .toList();

    return DataOutput(
      total: result['total'] as int? ?? 0,
      modelList: modelList,
    );
  }
}

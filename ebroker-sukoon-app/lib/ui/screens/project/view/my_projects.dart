import 'package:ebroker/commons/utils/property_project_add_button_tap.dart';
import 'package:ebroker/exports/main_export.dart';
import 'package:ebroker/ui/screens/home/widgets/custom_refresh_indicator.dart';
import 'package:ebroker/ui/screens/home/widgets/project_card_horizontal.dart';
import 'package:ebroker/ui/screens/my_listings_screen.dart'; // Import for FilterType enum
import 'package:flutter/material.dart';

class MyProjects extends StatefulWidget {
  const MyProjects({super.key});

  static Route<dynamic> route(RouteSettings settings) {
    return CupertinoPageRoute(
      builder: (context) {
        return const MyProjects();
      },
    );
  }

  @override
  State<MyProjects> createState() => _MyProjectsState();
}

class _MyProjectsState extends State<MyProjects> {
  final ScrollController _scrollController = ScrollController();
  String selectedType = '';
  String selectedStatus = '';
  // Track temporary filter selections
  late String tempSelectedType;
  late String tempSelectedStatus;

  @override
  void initState() {
    tempSelectedType = selectedType;
    tempSelectedStatus = selectedStatus;
    unawaited(context.read<FetchMyProjectsListCubit>().fetchMyProjects());

    _scrollController.addListener(() async {
      if (_scrollController.position.pixels ==
          _scrollController.position.maxScrollExtent) {
        if (context.read<FetchMyProjectsListCubit>().hasMoreData()) {
          await context.read<FetchMyProjectsListCubit>().fetchMoreMyProjects(
            type: selectedType.toLowerCase(),
            status: selectedStatus.toLowerCase(),
          );
        }
      }
    });
    super.initState();
  }

  String statusText(String text) {
    if (text == '1') {
      return 'active'.translate(context);
    } else if (text == '0') {
      return 'inactive'.translate(context);
    } else if (text == 'rejected') {
      return 'rejected'.translate(context);
    } else if (text == 'pending') {
      return 'pending'.translate(context);
    } else if (text == 'expired') {
      return 'expired'.translate(context);
    }
    return '';
  }

  Color statusColor(String text) {
    if (text == '1') {
      return Colors.green;
    } else if (text == '0') {
      return Colors.orangeAccent;
    } else if (text == 'rejected') {
      return Colors.redAccent;
    } else if (text == 'pending') {
      return Colors.blue;
    } else if (text == 'expired') {
      return Colors.grey;
    }
    return Colors.transparent;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.color.primaryColor,
      appBar: CustomAppBar(
        title: 'myProjects'.translate(context),
        actions: [
          GestureDetector(
            onTap: showFilters,
            child: Container(
              margin: const EdgeInsetsDirectional.only(end: 8, bottom: 4),
              height: 40.rh(context),
              width: 40.rw(context),
              decoration: BoxDecoration(
                border: Border.all(
                  color: context.color.borderColor,
                ),
                borderRadius: BorderRadius.circular(4),
              ),
              alignment: Alignment.center,
              padding: EdgeInsets.all(4.rw(context)),
              child: CustomImage(
                imageUrl: AppIcons.filter,
                color: context.color.textColorDark,
                width: 24.rw(context),
                height: 24.rh(context),
              ),
            ),
          ),
        ],
      ),
      body: CustomRefreshIndicator(
        onRefresh: () async {
          await context.read<FetchMyProjectsListCubit>().fetchMyProjects(
            type: selectedType.toLowerCase(),
            status: selectedStatus.toLowerCase(),
          );
        },
        child: BlocBuilder<FetchMyProjectsListCubit, FetchMyProjectsListState>(
          builder: (context, state) {
            if (state is FetchMyProjectsListInProgress) {
              return UiUtils.buildHorizontalShimmer(context);
            }
            if (state is FetchMyProjectsListFail) {
              if (state.error is NoInternetConnectionError) {
                return NoInternet(
                  onRetry: () async {
                    await context
                        .read<FetchMyProjectsListCubit>()
                        .fetchMyProjects(
                          type: selectedType.toLowerCase(),
                          status: selectedStatus.toLowerCase(),
                        );
                  },
                );
              }
              return SomethingWentWrong(
                errorMessage: state.error.toString(),
              );
            }
            if (state is FetchMyProjectsListSuccess) {
              if (state.projects.isEmpty) {
                return NoDataFound(
                  title: 'noProjectAdded'.translate(context),
                  description: 'noProjectAddedDescription'.translate(context),
                  showMainButton: true,
                  mainButtonTitle: 'addProject'.translate(context),
                  onTapMainButton: _navigateToAddProject,
                  onTapRetry: () async {
                    await context
                        .read<FetchMyProjectsListCubit>()
                        .fetchMyProjects(
                          type: selectedType.toLowerCase(),
                          status: selectedStatus.toLowerCase(),
                        );
                  },
                );
              }
              return ListView.separated(
                separatorBuilder: (context, index) => const SizedBox(height: 8),
                physics: Constant.scrollPhysics,
                controller: _scrollController,
                padding: EdgeInsets.all(14.rs(context)),
                itemCount:
                    state.projects.length + (state.isLoadingMore ? 1 : 0),
                itemBuilder: (context, index) {
                  if (index >= state.projects.length) {
                    if (state.isLoadingMore) {
                      return Center(
                        child: UiUtils.progress(
                          height: 30.rh(context),
                          width: 30.rw(context),
                        ),
                      );
                    }
                    return const SizedBox();
                  }
                  final project = state.projects[index];
                  final requestStatus = project.requestStatus == 'approved'
                      ? project.status.toString()
                      : project.requestStatus.toString();
                  return ProjectHorizontalCard(
                    project: project,
                    isRejected: project.requestStatus == 'rejected',
                    statusButton: StatusButton(
                      lable: statusText(requestStatus),
                      color: statusColor(requestStatus).withValues(alpha: 0.2),
                      textColor: statusColor(requestStatus),
                    ),
                  );
                },
              );
              // return ProjectCard(title: "Hello",categoryIcon: ,);
            }
            if (state is FetchMyProjectsListFail) {
              return Center(
                child: CustomText(state.error.toString()),
              );
            }

            return Container();
          },
        ),
      ),
    );
  }

  Future<void> _navigateToAddProject() async {
    await handleAddPropertyOrProjectTap(context, PropertyAddType.project);
  }

  /// Shows dialog to complete profile
  Future<void> showFilters() async {
    // Reset temporary selections to current values when opening filter
    tempSelectedType = selectedType;
    tempSelectedStatus = selectedStatus;
    await showModalBottomSheet<dynamic>(
      context: context,
      showDragHandle: true,
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
      ),
      backgroundColor: context.color.secondaryColor,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Container(
              padding: const EdgeInsets.only(left: 16, right: 16, bottom: 16),
              color: context.color.secondaryColor,
              width: MediaQuery.of(context).size.width,
              child: Column(
                mainAxisSize: .min,
                crossAxisAlignment: .start,
                children: [
                  CustomText(
                    'filterTitle'.translate(context),
                    color: context.color.inverseSurface,
                    fontWeight: .bold,
                    fontSize: context.font.xl,
                  ),
                  const SizedBox(height: 16),
                  CustomText(
                    'status'.translate(context),
                    color: context.color.inverseSurface,
                    fontWeight: .bold,
                    fontSize: context.font.md,
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    runSpacing: 8,
                    children: [
                      buildFilterCheckbox(
                        'all'.translate(context),
                        tempSelectedStatus,
                        '',
                        FilterType.status,
                        setModalState,
                      ),
                      buildFilterCheckbox(
                        'approved'.translate(context),
                        tempSelectedStatus,
                        'approved',
                        FilterType.status,
                        setModalState,
                      ),
                      buildFilterCheckbox(
                        'rejected'.translate(context),
                        tempSelectedStatus,
                        'rejected',
                        FilterType.status,
                        setModalState,
                      ),
                      buildFilterCheckbox(
                        'pending'.translate(context),
                        tempSelectedStatus,
                        'pending',
                        FilterType.status,
                        setModalState,
                      ),
                      buildFilterCheckbox(
                        'expired'.translate(context),
                        tempSelectedStatus,
                        'expired',
                        FilterType.status,
                        setModalState,
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  CustomText(
                    'type'.translate(context),
                    color: context.color.inverseSurface,
                    fontWeight: .bold,
                    fontSize: context.font.md,
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    runSpacing: 8,
                    children: [
                      buildFilterCheckbox(
                        'all'.translate(context),
                        tempSelectedType,
                        '',
                        FilterType.propertyType,
                        setModalState,
                      ),
                      buildFilterCheckbox(
                        'Upcoming'.translate(context),
                        tempSelectedType,
                        'upcoming',
                        FilterType.propertyType,
                        setModalState,
                      ),
                      buildFilterCheckbox(
                        'under_construction'.translate(context),
                        tempSelectedType,
                        'under_construction',
                        FilterType.propertyType,
                        setModalState,
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  UiUtils.buildButton(
                    context,
                    onPressed: () async {
                      // Apply the temporary selections
                      setState(() {
                        selectedType = tempSelectedType;
                        selectedStatus = tempSelectedStatus;
                      });
                      // Close the modal
                      Navigator.pop(context);
                      // Fetch properties with new filters
                      await context
                          .read<FetchMyProjectsListCubit>()
                          .fetchMyProjects(
                            type: selectedType.toLowerCase(),
                            status: selectedStatus.toLowerCase(),
                          );
                    },
                    height: 48.rh(context),
                    buttonTitle: 'applyFilter'.translate(context),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget buildFilterCheckbox(
    String title,
    String currentValue,
    String optionValue,
    FilterType filterType,
    StateSetter setModalState,
  ) {
    final isSelected = currentValue.toLowerCase() == optionValue.toLowerCase();

    return GestureDetector(
      onTap: () {
        setModalState(() {
          switch (filterType) {
            case FilterType.status:
              tempSelectedStatus = optionValue.toLowerCase();
            case FilterType.propertyType:
              tempSelectedType = optionValue.toLowerCase();
          }
        });
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        margin: const EdgeInsetsDirectional.only(end: 12),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(4),
          border: Border.all(
            color: isSelected
                ? context.color.tertiaryColor
                : context.color.borderColor,
            width: isSelected ? 2 : 1,
          ),
          color: isSelected
              ? context.color.tertiaryColor
              : context.color.primaryColor,
        ),
        child: CustomText(
          title,
          color: isSelected
              ? context.color.buttonColor
              : context.color.inverseSurface,
          fontWeight: isSelected ? .bold : .w600,
        ),
      ),
    );
  }
}

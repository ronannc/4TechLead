import 'package:equatable/equatable.dart';

class DeliveryCaseAudit extends Equatable {
  const DeliveryCaseAudit({
    required this.id,
    required this.taskRef,
    this.clickupTaskId,
    this.title,
    this.currentStage,
    this.currentAssigneeId,
    required this.statusHistory,
    required this.assignmentHistory,
    required this.activities,
  });

  factory DeliveryCaseAudit.fromJson(Map<String, dynamic> json) {
    return DeliveryCaseAudit(
      id: json['id'] as int,
      taskRef: json['task_ref'] as String,
      clickupTaskId: json['clickup_task_id'] as String?,
      title: json['title'] as String?,
      currentStage: json['current_stage'] as String?,
      currentAssigneeId: (json['current_assignee'] as Map<String, dynamic>?)?['id']
          as int?,
      statusHistory: _list(
        json['status_history'],
        DeliveryCaseStatusChange.fromJson,
      ),
      assignmentHistory: _list(
        json['assignment_history'],
        DeliveryCaseAssignment.fromJson,
      ),
      activities: _list(
        json['activities'],
        DeliveryCaseActivity.fromJson,
      ),
    );
  }

  final int id;
  final String taskRef;
  final String? clickupTaskId;
  final String? title;
  final String? currentStage;
  final int? currentAssigneeId;
  final List<DeliveryCaseStatusChange> statusHistory;
  final List<DeliveryCaseAssignment> assignmentHistory;
  final List<DeliveryCaseActivity> activities;

  @override
  List<Object?> get props => [
    id,
    taskRef,
    clickupTaskId,
    title,
    currentStage,
    currentAssigneeId,
    statusHistory,
    assignmentHistory,
    activities,
  ];

  static List<T> _list<T>(
    dynamic value,
    T Function(Map<String, dynamic>) parser,
  ) {
    return [
      for (final item in (value as List<dynamic>? ?? const []))
        parser(item as Map<String, dynamic>),
    ];
  }
}

class DeliveryCaseStatusChange extends Equatable {
  const DeliveryCaseStatusChange({
    this.fromStage,
    required this.toStage,
    this.occurredAt,
    this.statusChangedById,
    this.assigneeId,
  });

  factory DeliveryCaseStatusChange.fromJson(Map<String, dynamic> json) {
    return DeliveryCaseStatusChange(
      fromStage: json['from_stage'] as String?,
      toStage: json['to_stage'] as String,
      occurredAt: _date(json['occurred_at']),
      statusChangedById: _personId(json['status_changed_by']),
      assigneeId: _personId(json['assignee']),
    );
  }

  final String? fromStage;
  final String toStage;
  final DateTime? occurredAt;
  final int? statusChangedById;
  final int? assigneeId;

  @override
  List<Object?> get props => [
    fromStage,
    toStage,
    occurredAt,
    statusChangedById,
    assigneeId,
  ];
}

class DeliveryCaseAssignment extends Equatable {
  const DeliveryCaseAssignment({
    required this.assigneeId,
    required this.assignedAt,
    this.unassignedAt,
  });

  factory DeliveryCaseAssignment.fromJson(Map<String, dynamic> json) {
    return DeliveryCaseAssignment(
      assigneeId: _personId(json['assignee']) ?? 0,
      assignedAt: _date(json['assigned_at']),
      unassignedAt: _date(json['unassigned_at']),
    );
  }

  final int assigneeId;
  final DateTime? assignedAt;
  final DateTime? unassignedAt;

  @override
  List<Object?> get props => [assigneeId, assignedAt, unassignedAt];
}

class DeliveryCaseActivity extends Equatable {
  const DeliveryCaseActivity({
    required this.activityType,
    this.assigneeId,
    this.authorId,
    this.reviewerId,
    this.repository,
    this.branch,
    this.pullRequestNumber,
    this.commitSha,
    this.workflowRunId,
    this.occurredAt,
  });

  factory DeliveryCaseActivity.fromJson(Map<String, dynamic> json) {
    return DeliveryCaseActivity(
      activityType: json['activity_type'] as String,
      assigneeId: _personId(json['assignee']),
      authorId: _personId(json['author']),
      reviewerId: _personId(json['reviewer']),
      repository: json['repository'] as String?,
      branch: json['branch'] as String?,
      pullRequestNumber: json['pull_request_number'] as String?,
      commitSha: json['commit_sha'] as String?,
      workflowRunId: json['workflow_run_id'] as String?,
      occurredAt: _date(json['occurred_at']),
    );
  }

  final String activityType;
  final int? assigneeId;
  final int? authorId;
  final int? reviewerId;
  final String? repository;
  final String? branch;
  final String? pullRequestNumber;
  final String? commitSha;
  final String? workflowRunId;
  final DateTime? occurredAt;

  @override
  List<Object?> get props => [
    activityType,
    assigneeId,
    authorId,
    reviewerId,
    repository,
    branch,
    pullRequestNumber,
    commitSha,
    workflowRunId,
    occurredAt,
  ];
}

DateTime? _date(dynamic value) {
  return value is String ? DateTime.tryParse(value) : null;
}

int? _personId(dynamic value) {
  return (value as Map<String, dynamic>?)?['id'] as int?;
}

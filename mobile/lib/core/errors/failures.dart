import 'package:equatable/equatable.dart';

/// Base failure class for error handling
abstract class Failure extends Equatable {
  final String message;
  final int? code;
  
  const Failure({required this.message, this.code});
  
  @override
  List<Object?> get props => [message, code];
}

/// Server-side failures
class ServerFailure extends Failure {
  const ServerFailure({required super.message, super.code});
}

/// Network connectivity failures
class NetworkFailure extends Failure {
  const NetworkFailure({super.message = 'Nu există conexiune la internet'});
}

/// Authentication failures
class AuthFailure extends Failure {
  const AuthFailure({required super.message, super.code});
}

/// Validation failures
class ValidationFailure extends Failure {
  final Map<String, String>? fieldErrors;
  
  const ValidationFailure({
    required super.message,
    this.fieldErrors,
  });
  
  @override
  List<Object?> get props => [message, fieldErrors];
}

/// Cache/Local storage failures
class CacheFailure extends Failure {
  const CacheFailure({super.message = 'Eroare la accesarea datelor locale'});
}

/// Unknown failures
class UnknownFailure extends Failure {
  const UnknownFailure({super.message = 'A apărut o eroare neașteptată'});
}

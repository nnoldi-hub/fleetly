import 'package:equatable/equatable.dart';

/// User model
class User extends Equatable {
  final int id;
  final String username;
  final String email;
  final String? firstName;
  final String? lastName;
  final String role;
  final String? roleName;
  final int? companyId;
  final String? companyName;
  final String? phone;
  final String? avatar;

  const User({
    required this.id,
    required this.username,
    required this.email,
    this.firstName,
    this.lastName,
    required this.role,
    this.roleName,
    this.companyId,
    this.companyName,
    this.phone,
    this.avatar,
  });

  String get fullName {
    if (firstName != null && lastName != null) {
      return '$firstName $lastName';
    }
    return firstName ?? lastName ?? username;
  }

  String get initials {
    if (firstName != null && lastName != null) {
      return '${firstName![0]}${lastName![0]}'.toUpperCase();
    }
    return username.substring(0, 2).toUpperCase();
  }

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      username: json['username'] ?? '',
      email: json['email'] ?? '',
      firstName: json['first_name'],
      lastName: json['last_name'],
      role: json['role'] ?? 'user',
      roleName: json['role_name'],
      companyId: json['company_id'] is String 
          ? int.tryParse(json['company_id']) 
          : json['company_id'],
      companyName: json['company_name'] ?? json['company']?['name'],
      phone: json['phone'],
      avatar: json['avatar'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'username': username,
      'email': email,
      'first_name': firstName,
      'last_name': lastName,
      'role': role,
      'role_name': roleName,
      'company_id': companyId,
      'company_name': companyName,
      'phone': phone,
      'avatar': avatar,
    };
  }

  User copyWith({
    int? id,
    String? username,
    String? email,
    String? firstName,
    String? lastName,
    String? role,
    String? roleName,
    int? companyId,
    String? companyName,
    String? phone,
    String? avatar,
  }) {
    return User(
      id: id ?? this.id,
      username: username ?? this.username,
      email: email ?? this.email,
      firstName: firstName ?? this.firstName,
      lastName: lastName ?? this.lastName,
      role: role ?? this.role,
      roleName: roleName ?? this.roleName,
      companyId: companyId ?? this.companyId,
      companyName: companyName ?? this.companyName,
      phone: phone ?? this.phone,
      avatar: avatar ?? this.avatar,
    );
  }

  @override
  List<Object?> get props => [
        id,
        username,
        email,
        firstName,
        lastName,
        role,
        companyId,
      ];
}

/// Auth tokens model
class AuthTokens {
  final String accessToken;
  final String refreshToken;
  final String tokenType;
  final int expiresIn;

  const AuthTokens({
    required this.accessToken,
    required this.refreshToken,
    this.tokenType = 'Bearer',
    this.expiresIn = 3600,
  });

  factory AuthTokens.fromJson(Map<String, dynamic> json) {
    return AuthTokens(
      accessToken: json['access_token'] ?? '',
      refreshToken: json['refresh_token'] ?? '',
      tokenType: json['token_type'] ?? 'Bearer',
      expiresIn: json['expires_in'] ?? 3600,
    );
  }
}

/// Login response model
class LoginResponse {
  final AuthTokens tokens;
  final User user;

  const LoginResponse({
    required this.tokens,
    required this.user,
  });

  factory LoginResponse.fromJson(Map<String, dynamic> json) {
    return LoginResponse(
      tokens: AuthTokens.fromJson(json['tokens']),
      user: User.fromJson(json['user']),
    );
  }
}

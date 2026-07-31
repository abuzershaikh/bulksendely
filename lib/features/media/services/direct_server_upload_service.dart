import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:autoreply/core/network/api_client.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';

class UploadedMedia {
  final String url;
  final String filename;
  final int size;
  final String contentType;

  const UploadedMedia({
    required this.url,
    required this.filename,
    required this.size,
    required this.contentType,
  });
}

class DirectServerUploadService {
  Future<UploadedMedia?> pickAndUpload({
    required String folder,
    List<String>? allowedExtensions,
    FileType type = FileType.any,
  }) async {
    final pickerType =
        allowedExtensions != null && allowedExtensions.isNotEmpty
            ? FileType.custom
            : type;
    final result = await FilePicker.platform.pickFiles(
      type: pickerType,
      allowedExtensions: allowedExtensions,
      withData: true,
    );

    if (result == null || result.files.isEmpty) {
      return null;
    }

    final file = result.files.single;
    final filename = file.name;
    final bytes = file.bytes;
    final path = file.path;

    if (bytes == null && path == null) {
      throw Exception('Selected file data is not available');
    }

    final optimized = await _optimizeBeforeUpload(
      filename: filename,
      bytes: bytes,
      path: path,
    );
    final uploadBytes =
        optimized.bytes ?? (optimized.path != null ? await File(optimized.path!).readAsBytes() : null);
    if (uploadBytes == null || uploadBytes.isEmpty) {
      throw Exception('Unable to read selected media file');
    }

    final mimeType = _guessContentType(optimized.filename);
    final accessToken = await ApiClient.requireWaziperAccessToken();
    final response = await ApiClient.post('api/media/upload_base64', {
      'access_token': accessToken,
      'folder': folder,
      'filename': optimized.filename,
      'mime_type': mimeType,
      'data_base64': base64Encode(uploadBytes),
    });

    if (response['status'] != 'success') {
      throw Exception(response['message'] ?? 'Server media upload failed');
    }
    final data = Map<String, dynamic>.from(
      (response['data'] as Map?)?.cast<String, dynamic>() ?? const {},
    );

    return UploadedMedia(
      url: data['url']?.toString() ?? '',
      filename: data['filename']?.toString() ?? optimized.filename,
      size: (data['size'] as num?)?.toInt() ?? uploadBytes.length,
      contentType: data['mime_type']?.toString() ?? mimeType,
    );
  }

  Future<_PreparedUpload> _optimizeBeforeUpload({
    required String filename,
    required Uint8List? bytes,
    required String? path,
  }) async {
    final lower = filename.toLowerCase();
    if (_isImage(lower)) {
      final sourceBytes =
          bytes ?? (path != null ? await File(path).readAsBytes() : null);
      if (sourceBytes == null) {
        return _PreparedUpload(filename: filename, bytes: bytes, path: path);
      }

      try {
        final compressed = await FlutterImageCompress.compressWithList(
          sourceBytes,
          quality: 72,
          minWidth: 1280,
          minHeight: 1280,
          format: _isPng(lower) ? CompressFormat.png : CompressFormat.jpeg,
        );
        if (compressed.isNotEmpty && compressed.length < sourceBytes.length) {
          return _PreparedUpload(
            filename: _isPng(lower) ? filename : _swapExt(filename, 'jpg'),
            bytes: compressed,
          );
        }
      } catch (_) {}
      return _PreparedUpload(filename: filename, bytes: sourceBytes, path: path);
    }

    return _PreparedUpload(filename: filename, bytes: bytes, path: path);
  }

  String _guessContentType(String filename) {
    final parts = filename.toLowerCase().split('.');
    final ext = parts.length > 1 ? parts.last : '';
    switch (ext) {
      case 'jpg':
      case 'jpeg':
        return 'image/jpeg';
      case 'png':
        return 'image/png';
      case 'webp':
        return 'image/webp';
      case 'mp4':
        return 'video/mp4';
      case 'mov':
        return 'video/quicktime';
      case 'mkv':
        return 'video/x-matroska';
      case 'webm':
        return 'video/webm';
      case 'mp3':
        return 'audio/mpeg';
      case 'wav':
        return 'audio/wav';
      case 'pdf':
        return 'application/pdf';
      default:
        return 'application/octet-stream';
    }
  }

  bool _isImage(String name) {
    return name.endsWith('.jpg') ||
        name.endsWith('.jpeg') ||
        name.endsWith('.png') ||
        name.endsWith('.webp');
  }

  bool _isPng(String name) => name.endsWith('.png');

  String _swapExt(String filename, String ext) {
    final dot = filename.lastIndexOf('.');
    if (dot <= 0) {
      return '$filename.$ext';
    }
    return '${filename.substring(0, dot)}.$ext';
  }
}

class _PreparedUpload {
  final String filename;
  final Uint8List? bytes;
  final String? path;

  const _PreparedUpload({
    required this.filename,
    this.bytes,
    this.path,
  });
}

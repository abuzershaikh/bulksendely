import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:file_picker/file_picker.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';

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

class CloudflareUploadService {
  static const String _uploadEndpoint =
      'https://marketingpro-media-worker.zestbizar.workers.dev/upload';

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

    final request = http.MultipartRequest(
      'POST',
      Uri.parse(_uploadEndpoint),
    );

    request.fields['folder'] = folder;
    final contentType = _mediaTypeForFilename(optimized.filename);

    if (optimized.bytes != null) {
      request.files.add(
        http.MultipartFile.fromBytes(
          'file',
          optimized.bytes!,
          filename: optimized.filename,
          contentType: contentType,
        ),
      );
    } else {
      request.files.add(
        await http.MultipartFile.fromPath(
          'file',
          optimized.path!,
          filename: optimized.filename,
          contentType: contentType,
        ),
      );
    }

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw Exception('Upload failed with status ${response.statusCode}');
    }

    final body = response.body;
    final json = body.isNotEmpty ? _decodeJson(body) : <String, dynamic>{};
    if (json['ok'] != true) {
      throw Exception(json['error']?.toString() ?? 'Upload failed');
    }

    return UploadedMedia(
      url: json['url']?.toString() ?? '',
      filename: json['filename']?.toString() ?? optimized.filename,
      size: (json['size'] as num?)?.toInt() ?? file.size,
      contentType:
          json['contentType']?.toString() ??
          _guessContentType(optimized.filename),
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

  Map<String, dynamic> _decodeJson(String body) {
    return HttpJson.decode(body);
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

  MediaType _mediaTypeForFilename(String filename) {
    final parts = _guessContentType(filename).split('/');
    final type = parts.isNotEmpty && parts.first.isNotEmpty
        ? parts.first
        : 'application';
    final subtype = parts.length > 1 && parts[1].isNotEmpty
        ? parts[1]
        : 'octet-stream';
    return MediaType(type, subtype);
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

class HttpJson {
  static Map<String, dynamic> decode(String body) {
    return Map<String, dynamic>.from(
      body.isEmpty ? const <String, dynamic>{} : jsonDecode(body) as Map,
    );
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

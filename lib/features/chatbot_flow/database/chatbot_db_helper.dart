import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import '../models/flow_model.dart';

class ChatbotDBHelper {
  static final ChatbotDBHelper instance = ChatbotDBHelper._init();
  static Database? _database;

  ChatbotDBHelper._init();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('chatbot_flows.db');
    return _database!;
  }

  Future<Database> _initDB(String filePath) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, filePath);

    return await openDatabase(
      path,
      version: 1,
      onCreate: _createDB,
    );
  }

  Future _createDB(Database db, int version) async {
    const idType = 'TEXT PRIMARY KEY';
    const textType = 'TEXT NOT NULL';
    const intType = 'INTEGER NOT NULL';

    await db.execute('''
CREATE TABLE chatbot_flows (
  uuid $idType,
  name $textType,
  status $textType,
  version $intType,
  canvas_json TEXT,
  runtime_json TEXT,
  updated_at $intType
)
''');
  }

  Future<ChatbotFlow> createFlow(ChatbotFlow flow) async {
    final db = await instance.database;
    await db.insert('chatbot_flows', flow.toMap());
    return flow;
  }

  Future<ChatbotFlow?> readFlow(String uuid) async {
    final db = await instance.database;
    final maps = await db.query(
      'chatbot_flows',
      columns: ['uuid', 'name', 'status', 'version', 'canvas_json', 'runtime_json', 'updated_at'],
      where: 'uuid = ?',
      whereArgs: [uuid],
    );

    if (maps.isNotEmpty) {
      return ChatbotFlow.fromMap(maps.first);
    } else {
      return null;
    }
  }

  Future<List<ChatbotFlow>> readAllFlows() async {
    final db = await instance.database;
    const orderBy = 'updated_at DESC';
    final result = await db.query('chatbot_flows', orderBy: orderBy);
    return result.map((json) => ChatbotFlow.fromMap(json)).toList();
  }

  Future<int> updateFlow(ChatbotFlow flow) async {
    final db = await instance.database;
    return db.update(
      'chatbot_flows',
      flow.toMap(),
      where: 'uuid = ?',
      whereArgs: [flow.uuid],
    );
  }

  Future<int> deleteFlow(String uuid) async {
    final db = await instance.database;
    return await db.delete(
      'chatbot_flows',
      where: 'uuid = ?',
      whereArgs: [uuid],
    );
  }

  Future close() async {
    final db = await instance.database;
    db.close();
  }
}

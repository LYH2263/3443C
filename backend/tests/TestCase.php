<?php

namespace tests;

use think\App;
use think\facade\Db;
use Firebase\JWT\JWT;
use app\model\User;
use app\model\MemberLevel;
use app\model\Album;
use app\model\AlbumPage;
use app\model\AlbumCategory;
use app\model\AccessLog;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static $app;

    protected static $dbInitialized = false;

    protected $testUsers = [];
    protected $testAlbums = [];
    protected $testPages = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!self::$app) {
            self::$app = new App();
            self::$app->initialize();
        }

        if (!self::$dbInitialized) {
            self::initializeTestDatabase();
            self::$dbInitialized = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Db::startTrans();
    }

    protected function tearDown(): void
    {
        Db::rollback();
        parent::tearDown();
    }

    protected static function initializeTestDatabase(): void
    {
        $dbPath = env('DB_PATH');
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }

        self::runSqliteSchema();
    }

    protected static function runSqliteSchema(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS "member_levels" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" VARCHAR(50) NOT NULL,
  "level" INTEGER NOT NULL DEFAULT 0,
  "description" VARCHAR(255) DEFAULT '',
  "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "users" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "username" VARCHAR(50) NOT NULL,
  "password" VARCHAR(255) NOT NULL,
  "nickname" VARCHAR(100) DEFAULT '',
  "email" VARCHAR(100) DEFAULT '',
  "phone" VARCHAR(20) DEFAULT '',
  "avatar" VARCHAR(500) DEFAULT '',
  "role" VARCHAR(20) NOT NULL DEFAULT 'user',
  "member_level_id" INTEGER DEFAULT 1,
  "status" INTEGER NOT NULL DEFAULT 1,
  "last_login_at" DATETIME DEFAULT NULL,
  "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "album_categories" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" VARCHAR(100) NOT NULL,
  "sort_order" INTEGER DEFAULT 0,
  "status" INTEGER NOT NULL DEFAULT 1,
  "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "albums" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "title" VARCHAR(200) NOT NULL,
  "description" TEXT,
  "cover_image" VARCHAR(500) DEFAULT '',
  "background_image" VARCHAR(500) DEFAULT '',
  "category_id" INTEGER DEFAULT NULL,
  "min_level" INTEGER NOT NULL DEFAULT 0,
  "share_password" VARCHAR(50) DEFAULT '',
  "qrcode_image" VARCHAR(500) DEFAULT '',
  "qrcode_logo" VARCHAR(500) DEFAULT '',
  "qrcode_text_line1" VARCHAR(100) DEFAULT '',
  "qrcode_text_line2" VARCHAR(100) DEFAULT '',
  "view_count" INTEGER DEFAULT 0,
  "status" INTEGER NOT NULL DEFAULT 1,
  "sort_order" INTEGER DEFAULT 0,
  "creator_id" INTEGER DEFAULT NULL,
  "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "album_pages" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "album_id" INTEGER NOT NULL,
  "page_number" INTEGER NOT NULL,
  "image" VARCHAR(500) NOT NULL,
  "title" VARCHAR(200) DEFAULT '',
  "description" TEXT,
  "sort_order" INTEGER DEFAULT 0,
  "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "background_images" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" VARCHAR(200) NOT NULL,
  "path" VARCHAR(500) NOT NULL,
  "thumb_path" VARCHAR(500) DEFAULT '',
  "category" VARCHAR(50) DEFAULT 'default',
  "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS "access_logs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "album_id" INTEGER NOT NULL,
  "user_id" INTEGER DEFAULT NULL,
  "ip" VARCHAR(45) DEFAULT '',
  "user_agent" VARCHAR(500) DEFAULT '',
  "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP
);
SQL;

        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            Db::execute($stmt);
        }
    }

    protected function createMemberLevels(): array
    {
        $levels = [];
        $levelData = [
            ['name' => '普通会员', 'level' => 0, 'description' => '注册即为普通会员'],
            ['name' => '银牌会员', 'level' => 1, 'description' => '银牌会员'],
            ['name' => '金牌会员', 'level' => 2, 'description' => '金牌会员'],
            ['name' => 'VIP会员', 'level' => 3, 'description' => 'VIP会员'],
        ];
        foreach ($levelData as $data) {
            $levels[] = MemberLevel::create($data);
        }
        return $levels;
    }

    protected function createCategory(string $name = '测试分类'): AlbumCategory
    {
        return AlbumCategory::create([
            'name' => $name,
            'sort_order' => 1,
            'status' => 1,
        ]);
    }

    protected function createUser(array $overrides = []): User
    {
        $defaults = [
            'username' => 'test_user_' . uniqid(),
            'password' => 'Test@123456',
            'nickname' => '测试用户',
            'role' => 'user',
            'member_level_id' => 1,
            'status' => 1,
        ];
        $data = array_merge($defaults, $overrides);
        $user = User::create($data);
        return $user;
    }

    protected function createAlbum(array $overrides = []): Album
    {
        $defaults = [
            'title' => '测试画册_' . uniqid(),
            'description' => '这是一个测试画册的描述',
            'cover_image' => '/images/test_cover.png',
            'background_image' => '/images/test_bg.png',
            'category_id' => null,
            'min_level' => 0,
            'share_password' => '',
            'view_count' => 0,
            'status' => 1,
            'sort_order' => 0,
            'creator_id' => null,
            'qrcode_text_line1' => '扫码查看',
            'qrcode_text_line2' => '测试画册',
        ];
        $data = array_merge($defaults, $overrides);
        return Album::create($data);
    }

    protected function createAlbumPage(int $albumId, int $pageNumber = 1): AlbumPage
    {
        return AlbumPage::create([
            'album_id' => $albumId,
            'page_number' => $pageNumber,
            'image' => "/images/album_{$albumId}_page_{$pageNumber}.png",
            'title' => "第{$pageNumber}页",
            'sort_order' => $pageNumber - 1,
        ]);
    }

    protected function createJwtToken(int $userId): string
    {
        $key = env('JWT_SECRET', 'flipbook_jwt_secret_key_2024');
        $payload = [
            'uid' => $userId,
            'iat' => time(),
            'exp' => time() + 86400 * 7,
        ];
        return JWT::encode($payload, $key, 'HS256');
    }

    protected function createAuthorizationHeader(int $userId): string
    {
        return 'Bearer ' . $this->createJwtToken($userId);
    }

    protected function buildRequest(string $method, string $uri, array $headers = [], $body = null): \think\Request
    {
        $request = \think\facade\Request::create($uri, $method);

        if (!empty($headers)) {
            $server = [];
            foreach ($headers as $key => $value) {
                $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
                $server[$serverKey] = $value;
            }
            $request->withServer($server);
        }

        if ($body !== null) {
            if (is_array($body)) {
                $request->withPost($body);
            } elseif (is_string($body)) {
                $request->withBody($body);
            }
        }

        return $request;
    }

    protected function runController(string $controller, string $action, \think\Request $request, array $params = []): \think\Response
    {
        $controllerClass = "app\\controller\\{$controller}";
        $instance = new $controllerClass();

        $response = call_user_func_array([$instance, $action], [$request, ...array_values($params)]);

        if (!$response instanceof \think\Response) {
            $response = json($response);
        }

        return $response;
    }

    protected function jsonToArray(\think\Response $response): array
    {
        return json_decode($response->getContent(), true) ?: [];
    }

    protected function assertResponseCode(\think\Response $response, int $expectedCode, string $message = ''): void
    {
        $content = $this->jsonToArray($response);
        $actualCode = $content['code'] ?? $response->getCode();
        $this->assertEquals($expectedCode, $actualCode, $message ?: "期望响应码 {$expectedCode}，实际 {$actualCode}");
    }

    protected function assertJsonSuccess(\think\Response $response, string $message = ''): void
    {
        $this->assertResponseCode($response, 200, $message ?: '期望响应为成功状态');
    }

    protected function assertJsonError(\think\Response $response, int $expectedCode, string $message = ''): void
    {
        $this->assertResponseCode($response, $expectedCode, $message ?: "期望响应为错误码 {$expectedCode}");
    }
}

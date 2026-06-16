<?php

namespace tests\Integration;

use tests\TestCase;
use app\model\Album;
use app\model\AccessLog;
use app\model\User;
use app\model\MemberLevel;

class AlbumPublicDetailTest extends TestCase
{
    protected $levels;
    protected $guestAlbum;
    protected $silverAlbum;
    protected $goldAlbum;
    protected $vipAlbum;
    protected $passwordSilverAlbum;
    protected $unpublishedAlbum;
    protected $normalUser;
    protected $silverUser;
    protected $vipUser;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->levels = $this->createMemberLevels();
        $category = $this->createCategory();

        $this->guestAlbum = $this->createAlbum([
            'title' => '公开画册-所有人可见',
            'min_level' => 0,
            'category_id' => $category->id,
        ]);
        $this->createAlbumPage($this->guestAlbum->id, 1);
        $this->createAlbumPage($this->guestAlbum->id, 2);

        $this->silverAlbum = $this->createAlbum([
            'title' => '银牌画册-等级1',
            'min_level' => 1,
            'category_id' => $category->id,
        ]);
        $this->createAlbumPage($this->silverAlbum->id, 1);

        $this->goldAlbum = $this->createAlbum([
            'title' => '金牌画册-等级2',
            'min_level' => 2,
            'category_id' => $category->id,
        ]);

        $this->vipAlbum = $this->createAlbum([
            'title' => 'VIP画册-等级3',
            'min_level' => 3,
            'category_id' => $category->id,
        ]);

        $this->passwordSilverAlbum = $this->createAlbum([
            'title' => '带密码的银牌画册',
            'min_level' => 1,
            'share_password' => 'share123',
            'category_id' => $category->id,
        ]);
        $this->createAlbumPage($this->passwordSilverAlbum->id, 1);

        $this->unpublishedAlbum = $this->createAlbum([
            'title' => '未发布的画册',
            'min_level' => 0,
            'status' => 0,
            'category_id' => $category->id,
        ]);

        $this->normalUser = $this->createUser([
            'username' => 'normal_' . uniqid(),
            'nickname' => '普通用户',
            'role' => 'user',
            'member_level_id' => $this->levels[0]->id,
        ]);

        $this->silverUser = $this->createUser([
            'username' => 'silver_' . uniqid(),
            'nickname' => '银牌用户',
            'role' => 'user',
            'member_level_id' => $this->levels[1]->id,
        ]);

        $this->vipUser = $this->createUser([
            'username' => 'vip_' . uniqid(),
            'nickname' => 'VIP用户',
            'role' => 'user',
            'member_level_id' => $this->levels[3]->id,
        ]);

        $this->adminUser = $this->createUser([
            'username' => 'admin_' . uniqid(),
            'nickname' => '管理员',
            'role' => 'admin',
            'member_level_id' => $this->levels[0]->id,
        ]);
    }

    protected function callPublicDetailViaGet($albumId, array $headers = [], array $query = [])
    {
        $uri = "/api/public/albums/{$albumId}";
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }
        $request = $this->buildRequest('GET', $uri, $headers);
        return $this->runController('AlbumController', 'publicDetail', $request, [$albumId]);
    }

    protected function callPublicDetailViaPostVerify($albumId, array $headers = [], array $body = [])
    {
        $uri = "/api/public/albums/{$albumId}/verify";
        $request = $this->buildRequest('POST', $uri, $headers, $body);
        return $this->runController('AlbumController', 'publicDetail', $request, [$albumId]);
    }

    protected function callPublicDetailViaPost($albumId, array $headers = [], array $body = [])
    {
        $uri = "/api/public/albums/{$albumId}";
        $request = $this->buildRequest('POST', $uri, $headers, $body);
        return $this->runController('AlbumController', 'publicDetail', $request, [$albumId]);
    }

    public function test_访客访问公开画册成功()
    {
        $response = $this->callPublicDetailViaGet($this->guestAlbum->id);

        $this->assertJsonSuccess($response, '访客访问公开画册应返回200');
        $content = $this->jsonToArray($response);

        $this->assertFalse($content['data']['need_password'] ?? null, 'need_password 应为 false');
        $this->assertEquals($this->guestAlbum->id, $content['data']['album']['id'] ?? null, '返回的画册ID应匹配');
        $this->assertEquals($this->guestAlbum->title, $content['data']['album']['title'] ?? null, '返回的画册标题应匹配');
        $this->assertNotEmpty($content['data']['pages'] ?? [], '应返回画册页面');
        $this->assertCount(2, $content['data']['pages'] ?? [], '画册应有2个页面');
    }

    public function test_等级不足且无密码画册返回403()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->normalUser->id)];

        $response = $this->callPublicDetailViaGet($this->silverAlbum->id, $headers);

        $this->assertJsonError($response, 403, '等级不足且无密码应返回403');
        $content = $this->jsonToArray($response);
        $this->assertStringContainsString('等级不足', $content['message'] ?? '', '错误消息应包含等级不足提示');
    }

    public function test_等级不足但有密码_不传密码返回need_password()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->normalUser->id)];

        $response = $this->callPublicDetailViaGet($this->passwordSilverAlbum->id, $headers);

        $this->assertJsonSuccess($response, '不传密码时应返回200但提示需要密码');
        $content = $this->jsonToArray($response);
        $this->assertTrue($content['data']['need_password'] ?? false, 'need_password 应为 true');
        $this->assertNotEmpty($content['data']['album']['id'] ?? null, '应返回画册基本信息');
        $this->assertEmpty($content['data']['pages'] ?? null, '不应返回页面内容');
    }

    public function test_等级不足但有密码_传错密码返回need_password()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->normalUser->id)];

        $response = $this->callPublicDetailViaPostVerify(
            $this->passwordSilverAlbum->id,
            $headers,
            ['password' => 'wrong_password']
        );

        $this->assertJsonSuccess($response, '传错密码时应返回200但提示需要密码');
        $content = $this->jsonToArray($response);
        $this->assertTrue($content['data']['need_password'] ?? false, 'need_password 应为 true');
    }

    public function test_等级不足但有密码_传对密码返回完整内容()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->normalUser->id)];

        $response = $this->callPublicDetailViaPostVerify(
            $this->passwordSilverAlbum->id,
            $headers,
            ['password' => 'share123']
        );

        $this->assertJsonSuccess($response, '传对密码时应返回200成功');
        $content = $this->jsonToArray($response);
        $this->assertFalse($content['data']['need_password'] ?? null, 'need_password 应为 false');
        $this->assertEquals($this->passwordSilverAlbum->id, $content['data']['album']['id'] ?? null, '应返回正确的画册ID');
        $this->assertNotEmpty($content['data']['pages'] ?? [], '应返回画册页面内容');
    }

    public function test_管理员凭userLevel999绕过等级限制()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->adminUser->id)];

        $response = $this->callPublicDetailViaGet($this->vipAlbum->id, $headers);

        $this->assertJsonSuccess($response, '管理员访问VIP画册应成功');
        $content = $this->jsonToArray($response);
        $this->assertFalse($content['data']['need_password'] ?? null, 'need_password 应为 false');
        $this->assertEquals($this->vipAlbum->id, $content['data']['album']['id'] ?? null, '管理员应能绕过等级限制');
    }

    public function test_未发布画册返回404()
    {
        $response = $this->callPublicDetailViaGet($this->unpublishedAlbum->id);

        $this->assertJsonError($response, 404, '未发布画册应返回404');
        $content = $this->jsonToArray($response);
        $this->assertStringContainsString('不存在或未发布', $content['message'] ?? '', '错误消息应包含不存在或未发布提示');

        $notExistId = 99999;
        $response2 = $this->callPublicDetailViaGet($notExistId);
        $this->assertJsonError($response2, 404, '不存在的画册ID应返回404');
    }

    public function test_成功访问后view_count自增1且access_logs新增记录()
    {
        $initialCount = $this->guestAlbum->view_count;
        $initialLogCount = AccessLog::where('album_id', $this->guestAlbum->id)->count();

        $headers = ['Authorization' => $this->createAuthorizationHeader($this->normalUser->id)];
        $response = $this->callPublicDetailViaGet($this->guestAlbum->id, $headers);

        $this->assertJsonSuccess($response, '访问应成功');

        $updatedAlbum = Album::find($this->guestAlbum->id);
        $this->assertEquals(
            $initialCount + 1,
            $updatedAlbum->view_count,
            'view_count 应自增1'
        );

        $newLogCount = AccessLog::where('album_id', $this->guestAlbum->id)->count();
        $this->assertEquals(
            $initialLogCount + 1,
            $newLogCount,
            'access_logs 应新增1条记录'
        );

        $latestLog = AccessLog::where('album_id', $this->guestAlbum->id)
            ->order('id', 'desc')
            ->find();
        $this->assertNotNull($latestLog, '访问日志应存在');
        $this->assertEquals($this->guestAlbum->id, $latestLog->album_id, '日志的album_id应匹配');
        $this->assertEquals($this->normalUser->id, $latestLog->user_id, '日志的user_id应匹配登录用户');
        $this->assertNotEmpty($latestLog->created_at, '日志应记录创建时间');
    }

    public function test_访客访问也记录日志且user_id为null()
    {
        $response = $this->callPublicDetailViaGet($this->guestAlbum->id);
        $this->assertJsonSuccess($response);

        $latestLog = AccessLog::where('album_id', $this->guestAlbum->id)->find();
        $this->assertNotNull($latestLog);
        $this->assertNull($latestLog->user_id, '访客访问时user_id应为null');
    }

    public function test_三种请求方式_GET_行为一致()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->silverUser->id)];

        $respGet = $this->callPublicDetailViaGet($this->silverAlbum->id, $headers);
        $respPostVerify = $this->callPublicDetailViaPostVerify($this->silverAlbum->id, $headers);
        $respPost = $this->callPublicDetailViaPost($this->silverAlbum->id, $headers);

        $dataGet = $this->jsonToArray($respGet);
        $dataPostVerify = $this->jsonToArray($respPostVerify);
        $dataPost = $this->jsonToArray($respPost);

        $this->assertEquals(200, $dataGet['code'], 'GET 应返回200');
        $this->assertEquals(200, $dataPostVerify['code'], 'POST verify 应返回200');
        $this->assertEquals(200, $dataPost['code'], 'POST 应返回200');

        $this->assertEquals(
            $dataGet['data']['need_password'],
            $dataPostVerify['data']['need_password'],
            'GET与POST verify的need_password应一致'
        );
        $this->assertEquals(
            $dataGet['data']['album']['id'],
            $dataPostVerify['data']['album']['id'],
            'GET与POST verify的album_id应一致'
        );
        $this->assertEquals(
            $dataGet['data']['album']['id'],
            $dataPost['data']['album']['id'],
            'GET与POST的album_id应一致'
        );

        $this->assertEquals(
            count($dataGet['data']['pages'] ?? []),
            count($dataPostVerify['data']['pages'] ?? []),
            'GET与POST verify返回的页数应一致'
        );
    }

    public function test_三种请求方式_带密码行为一致()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->normalUser->id)];

        $respGetNoPwd = $this->callPublicDetailViaGet($this->passwordSilverAlbum->id, $headers);
        $respPostVerifyNoPwd = $this->callPublicDetailViaPostVerify($this->passwordSilverAlbum->id, $headers);
        $respPostNoPwd = $this->callPublicDetailViaPost($this->passwordSilverAlbum->id, $headers);

        $dataGetNoPwd = $this->jsonToArray($respGetNoPwd);
        $dataPostVerifyNoPwd = $this->jsonToArray($respPostVerifyNoPwd);
        $dataPostNoPwd = $this->jsonToArray($respPostNoPwd);

        $this->assertTrue($dataGetNoPwd['data']['need_password'] ?? false, 'GET不传密码应提示需要密码');
        $this->assertTrue($dataPostVerifyNoPwd['data']['need_password'] ?? false, 'POST verify不传密码应提示需要密码');
        $this->assertTrue($dataPostNoPwd['data']['need_password'] ?? false, 'POST不传密码应提示需要密码');

        $respGetPwd = $this->callPublicDetailViaGet($this->passwordSilverAlbum->id, $headers, ['password' => 'share123']);
        $respPostVerifyPwd = $this->callPublicDetailViaPostVerify($this->passwordSilverAlbum->id, $headers, ['password' => 'share123']);
        $respPostPwd = $this->callPublicDetailViaPost($this->passwordSilverAlbum->id, $headers, ['password' => 'share123']);

        $dataGetPwd = $this->jsonToArray($respGetPwd);
        $dataPostVerifyPwd = $this->jsonToArray($respPostVerifyPwd);
        $dataPostPwd = $this->jsonToArray($respPostPwd);

        $this->assertFalse($dataGetPwd['data']['need_password'] ?? true, 'GET传对密码应成功访问');
        $this->assertFalse($dataPostVerifyPwd['data']['need_password'] ?? true, 'POST verify传对密码应成功访问');
        $this->assertFalse($dataPostPwd['data']['need_password'] ?? true, 'POST传对密码应成功访问');
    }

    public function test_VIP用户可访问所有等级画册()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->vipUser->id)];

        foreach ([$this->guestAlbum, $this->silverAlbum, $this->goldAlbum, $this->vipAlbum] as $album) {
            $response = $this->callPublicDetailViaGet($album->id, $headers);
            $content = $this->jsonToArray($response);
            $this->assertEquals(200, $content['code'], "VIP用户访问画册[{$album->title}]应成功");
            $this->assertFalse($content['data']['need_password'] ?? true, "VIP用户访问画册[{$album->title}] need_password应为false");
        }
    }

    public function test_银牌用户可访问公开和银牌画册_但不能访问金牌画册()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->silverUser->id)];

        $respGuest = $this->callPublicDetailViaGet($this->guestAlbum->id, $headers);
        $this->assertJsonSuccess($respGuest, '银牌用户可访问公开画册');

        $respSilver = $this->callPublicDetailViaGet($this->silverAlbum->id, $headers);
        $this->assertJsonSuccess($respSilver, '银牌用户可访问银牌画册');

        $respGold = $this->callPublicDetailViaGet($this->goldAlbum->id, $headers);
        $this->assertJsonError($respGold, 403, '银牌用户不能访问金牌画册');
    }

    public function test_普通用户只能访问公开画册()
    {
        $headers = ['Authorization' => $this->createAuthorizationHeader($this->normalUser->id)];

        $respGuest = $this->callPublicDetailViaGet($this->guestAlbum->id, $headers);
        $this->assertJsonSuccess($respGuest, '普通用户可访问公开画册');

        $respSilver = $this->callPublicDetailViaGet($this->silverAlbum->id, $headers);
        $this->assertJsonError($respSilver, 403, '普通用户不能访问银牌画册');
    }
}

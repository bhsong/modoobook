<?php

use App\Core\CsrfGuard;

beforeEach(function () {
    $_SESSION = [];
    $_POST = [];
});

afterEach(function () {
    $_SESSION = [];
    $_POST = [];
});

test('testGenerate_토큰생성_세션저장확인', function () {
    $token = CsrfGuard::generate();

    // 반환값이 64자리 hex 문자열인지 확인
    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);
    expect(ctype_xdigit($token))->toBeTrue();

    // $_SESSION['csrf_token'] 에 저장됐는지 확인
    expect($_SESSION)->toHaveKey('csrf_token');
    expect($_SESSION['csrf_token'])->toBe($token);
});

test('testValidate_유효토큰_성공반환', function () {
    // 세션에 토큰을 미리 저장
    $token = CsrfGuard::generate();

    // POST 파라미터에 동일한 토큰 전달
    $_POST['_csrf_token'] = $token;

    $result = CsrfGuard::validate();

    expect($result)->toBeTrue();
});

test('testValidate_토큰누락_실패반환', function () {
    // 세션에 토큰은 있지만 POST에는 없음
    CsrfGuard::generate();

    // $_POST에 _csrf_token 키 없음 (beforeEach에서 이미 빈 배열로 초기화됨)
    $result = CsrfGuard::validate();

    expect($result)->toBeFalse();
});

test('testValidate_토큰불일치_실패반환', function () {
    // 세션에 정상 토큰 저장
    CsrfGuard::generate();

    // POST에 위조된 토큰 전달
    $_POST['_csrf_token'] = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    $result = CsrfGuard::validate();

    expect($result)->toBeFalse();
});

test('testValidate_검증후토큰재발급_확인', function () {
    // 최초 토큰 생성
    $originalToken = CsrfGuard::generate();
    $_POST['_csrf_token'] = $originalToken;

    // 검증 성공
    $result = CsrfGuard::validate();
    expect($result)->toBeTrue();

    // 검증 후 세션에 새 토큰이 발급돼야 함 (이전 토큰과 달라야 함)
    expect($_SESSION['csrf_token'])->toBeString();
    expect($_SESSION['csrf_token'])->not->toBe($originalToken);
    expect(strlen($_SESSION['csrf_token']))->toBe(64);
    expect(ctype_xdigit($_SESSION['csrf_token']))->toBeTrue();
});

test('testTokenField_hiddenInput_포함확인', function () {
    // 토큰 생성 후 tokenField 호출
    CsrfGuard::generate();
    $html = CsrfGuard::tokenField();

    expect($html)->toBeString();
    expect($html)->toContain('type="hidden"');
    expect($html)->toContain('name="_csrf_token"');
    // 실제 토큰 값도 포함돼야 함
    expect($html)->toContain($_SESSION['csrf_token']);
});

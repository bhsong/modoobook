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

test('testJournalSave_CSRF토큰없이_403반환', function () {
    // 세션에는 토큰이 있지만 POST에는 _csrf_token 없음
    CsrfGuard::generate();

    // _csrf_token 키 미포함 상태로 validate() 호출
    $result = CsrfGuard::validate();

    // 토큰 누락 → false 반환 → 비즈니스 로직 미실행 분기
    expect($result)->toBeFalse();
});

test('testJournalSave_유효토큰_정상저장', function () {
    // 세션에 토큰 생성
    $token = CsrfGuard::generate();

    // 유효한 토큰을 POST에 포함
    $_POST['_csrf_token'] = $token;

    $result = CsrfGuard::validate();

    // 유효한 토큰 → true 반환 → 비즈니스 로직 진행 가능
    expect($result)->toBeTrue();

    // 검증 후 세션 토큰이 재발급됨 (CSRF 토큰 재사용 방지)
    expect($_SESSION['csrf_token'])->toBeString();
    expect($_SESSION['csrf_token'])->not->toBe($token);
});

test('testLogin_CSRF토큰없이_거부', function () {
    // 로그인 폼 진입 시 세션에 토큰 발급
    CsrfGuard::generate();

    // 로그인 POST 요청에 _csrf_token 없이 전송
    // (beforeEach에서 $_POST는 이미 빈 배열)
    $result = CsrfGuard::validate();

    // 토큰 누락 → validate() false → 로그인 처리 거부
    expect($result)->toBeFalse();
});

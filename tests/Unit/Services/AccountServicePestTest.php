<?php

use App\Database\AccountRepository;
use App\Services\AccountService;
use App\Services\AuditLogger;
use Mockery as m;

beforeEach(function () {
    $this->accountRepoMock = m::mock(AccountRepository::class);
    $this->auditMock = m::mock(AuditLogger::class);
    $this->service = new AccountService($this->accountRepoMock, $this->auditMock);
});

afterEach(function () {
    m::close();
});

test('정상적인 계정 생성 성공', function () {
    $userId = 1;
    $data = [
        'accountName' => '신한은행 생활비',
        'parentAccountId' => 10,
    ];

    // 1. 부모 계정 조회 Mock (Level 2여야 함)
    $this->accountRepoMock->shouldReceive('findById')
        ->with(10)
        ->once()
        ->andReturn([
            'accountId' => 10,
            'accountLevel' => 2,
            'accountType' => 'ASSET',
        ]);

    // 2. 중복 검증 Mock (중복 없음)
    $this->accountRepoMock->shouldReceive('findAll')
        ->once()
        ->andReturn([]);

    // 3. 계정 생성 Mock
    $this->accountRepoMock->shouldReceive('create')
        ->once()
        ->andReturn(101);

    // 4. 감사 로그 Mock
    $this->auditMock->shouldReceive('log')->once();

    $result = $this->service->createAccount($data, $userId);

    expect($result['success'])->toBeTrue();
    expect($result['accountId'])->toBe(101);
});

test('부모 계정이 Level 2가 아니면 생성 실패', function () {
    $userId = 1;
    $data = [
        'accountName' => '잘못된 계정',
        'parentAccountId' => 5,
    ];

    // 부모가 Level 1(대분류)인 경우
    $this->accountRepoMock->shouldReceive('findById')
        ->with(5)
        ->once()
        ->andReturn([
            'accountId' => 5,
            'accountLevel' => 1,
            'accountType' => 'ASSET',
        ]);

    $result = $this->service->createAccount($data, $userId);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('중분류(2단계) 계정을 상위 계정으로 선택');
});

test('동일 부모 내 중복된 이름이면 생성 실패', function () {
    $userId = 1;
    $data = [
        'accountName' => '중복이름',
        'parentAccountId' => 10,
    ];

    $this->accountRepoMock->shouldReceive('findById')
        ->andReturn(['accountId' => 10, 'accountLevel' => 2, 'accountType' => 'ASSET']);

    // 이미 존재하는 계정이 있다고 반환
    $this->accountRepoMock->shouldReceive('findAll')
        ->once()
        ->andReturn([['accountId' => 99]]);

    $result = $this->service->createAccount($data, $userId);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('이미 존재하는 계정명');
});

test('시스템 계정 삭제 시도 시 거부', function () {
    $accountId = 1;
    $userId = 1;

    $this->accountRepoMock->shouldReceive('findById')
        ->with($accountId)
        ->once()
        ->andReturn([
            'accountId' => 1,
            'isSystem' => 1,
        ]);

    $this->auditMock->shouldReceive('logFailure')->once();

    $result = $this->service->deleteAccount($accountId, $userId);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('시스템 계정은 삭제할 수 없습니다');
});

test('하위 계정이 있는 경우 삭제 거부', function () {
    $accountId = 10;
    $userId = 1;

    $this->accountRepoMock->shouldReceive('findById')
        ->once()
        ->andReturn(['accountId' => 10, 'isSystem' => 0]);

    // 하위 계정 검색 시 결과 있음
    $this->accountRepoMock->shouldReceive('findAll')
        ->with(['parentAccountId' => 10])
        ->once()
        ->andReturn([['accountId' => 101]]);

    $result = $this->service->deleteAccount($accountId, $userId);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('하위 계정이 존재');
});

test('전표가 연결된 계정 삭제 거부', function () {
    $accountId = 101;
    $userId = 1;

    $this->accountRepoMock->shouldReceive('findById')
        ->once()
        ->andReturn(['accountId' => 101, 'isSystem' => 0]);

    $this->accountRepoMock->shouldReceive('findAll')->andReturn([]);

    // 연결된 전표 확인
    $this->accountRepoMock->shouldReceive('hasLinkedEntries')
        ->with(101)
        ->once()
        ->andReturn(true);

    $result = $this->service->deleteAccount($accountId, $userId);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('연결된 전표가 있어');
});

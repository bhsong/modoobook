<?php
/**
 * AccountServiceTest — 계정과목 Service 단위 테스트
 *
 * DB 없이 Service 로직만 검증합니다 (Repository는 Mock 처리).
 * AccountService 생성자 주입 리팩토링(2026-02-20) 이후
 * AccountRepository Mock을 직접 주입하여 완전한 격리가 가능합니다.
 */

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Services\AccountService;
use App\Services\AuditLogger;
use App\Database\AccountRepository;

class AccountServiceTest extends TestCase
{
    private AccountService $service;
    private MockObject $repoMock;
    private MockObject $auditMock;

    protected function setUp(): void
    {
        $this->repoMock  = $this->createMock(AccountRepository::class);
        $this->auditMock = $this->createMock(AuditLogger::class);

        // AccountRepository Mock을 직접 주입 — DB 없이 Service 로직만 단위 테스트
        $this->service = new AccountService($this->repoMock, $this->auditMock);
    }

    // =========================================================
    // createAccount() — 정상 케이스
    // =========================================================

    /** @test */
    public function 정상_입력으로_계정_생성_성공(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 5, 'accountLevel' => 2, 'accountType' => 'EXPENSE']);
        $this->repoMock->method('findAll')->willReturn([]);
        $this->repoMock->method('create')->willReturn(99);

        $result = $this->service->createAccount(
            ['accountName' => '편의점', 'parentAccountId' => 5],
            userId: 1
        );

        $this->assertTrue($result['success']);
        $this->assertSame(99, $result['accountId']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function 반환값에는_반드시_success_키가_존재해야_함(): void
    {
        $result = $this->service->createAccount([], userId: 1);

        $this->assertArrayHasKey('success', $result, "Service 반환에 'success' 키 필수");
    }

    // =========================================================
    // createAccount() — 입력값 검증
    // =========================================================

    /** @test */
    public function 계정명이_비어있으면_생성_실패(): void
    {
        $result = $this->service->createAccount(
            ['accountName' => '', 'parentAccountId' => 5],
            userId: 1
        );

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function 계정명이_공백만이면_생성_실패(): void
    {
        $result = $this->service->createAccount(
            ['accountName' => '   ', 'parentAccountId' => 5],
            userId: 1
        );

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function parentAccountId가_없으면_생성_실패(): void
    {
        $result = $this->service->createAccount(
            ['accountName' => '편의점'],
            userId: 1
        );

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function parentAccountId가_0이면_생성_실패(): void
    {
        $result = $this->service->createAccount(
            ['accountName' => '편의점', 'parentAccountId' => 0],
            userId: 1
        );

        $this->assertFalse($result['success']);
    }

    // =========================================================
    // createAccount() — 부모 계정 검증
    // =========================================================

    /** @test */
    public function 부모계정이_존재하지_않으면_생성_실패(): void
    {
        $this->repoMock->method('findById')->willReturn(null);

        $result = $this->service->createAccount(
            ['accountName' => '편의점', 'parentAccountId' => 999],
            userId: 1
        );

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function 부모계정_레벨이_1이면_생성_실패(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 1, 'accountLevel' => 1, 'accountType' => 'EXPENSE']);

        $result = $this->service->createAccount(
            ['accountName' => '편의점', 'parentAccountId' => 1],
            userId: 1
        );

        $this->assertFalse($result['success'], 'Level 1 부모는 COA 규칙 위반');
    }

    /** @test */
    public function 부모계정_레벨이_3이면_생성_실패(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 10, 'accountLevel' => 3, 'accountType' => 'EXPENSE']);

        $result = $this->service->createAccount(
            ['accountName' => '편의점', 'parentAccountId' => 10],
            userId: 1
        );

        $this->assertFalse($result['success'], 'Level 3 하위는 생성 불가 (COA 규칙)');
    }

    /** @test */
    public function 동일_부모_아래_계정명_중복이면_생성_실패(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 5, 'accountLevel' => 2, 'accountType' => 'EXPENSE']);
        $this->repoMock->method('findAll')
            ->willReturn([['accountId' => 50, 'accountName' => '편의점']]);

        $result = $this->service->createAccount(
            ['accountName' => '편의점', 'parentAccountId' => 5],
            userId: 1
        );

        $this->assertFalse($result['success']);
    }

    // =========================================================
    // deleteAccount() — 정상 케이스
    // =========================================================

    /** @test */
    public function 사용자_계정_삭제_성공(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 200, 'isSystem' => 0]);
        $this->repoMock->method('findAll')->willReturn([]);
        $this->repoMock->method('hasLinkedEntries')->willReturn(false);
        $this->repoMock->method('delete')->willReturn(true);

        $result = $this->service->deleteAccount(accountId: 200, userId: 1);

        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function 정상_삭제_시_repo_delete가_accountId로_호출됨(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 200, 'isSystem' => 0]);
        $this->repoMock->method('findAll')->willReturn([]);
        $this->repoMock->method('hasLinkedEntries')->willReturn(false);

        $this->repoMock->expects($this->once())
            ->method('delete')
            ->with(200);

        $this->service->deleteAccount(accountId: 200, userId: 1);
    }

    // =========================================================
    // deleteAccount() — 거부 케이스
    // =========================================================

    /** @test */
    public function 존재하지_않는_계정_삭제_시_실패(): void
    {
        $this->repoMock->method('findById')->willReturn(null);

        $result = $this->service->deleteAccount(accountId: 999, userId: 1);

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function isSystem_1인_계정_삭제_시도는_거부(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 1, 'isSystem' => 1]);

        $result = $this->service->deleteAccount(accountId: 1, userId: 1);

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function 시스템계정_삭제_시도에서_repo_delete는_호출되지_않음(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 1, 'isSystem' => 1]);

        $this->repoMock->expects($this->never())->method('delete');

        $this->service->deleteAccount(accountId: 1, userId: 1);
    }

    /** @test */
    public function 하위계정이_존재하면_삭제_거부(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 100, 'isSystem' => 0]);
        $this->repoMock->method('findAll')
            ->willReturn([['accountId' => 101, 'parentAccountId' => 100]]);

        $result = $this->service->deleteAccount(accountId: 100, userId: 1);

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function 전표에_연결된_계정은_삭제_거부(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 200, 'isSystem' => 0]);
        $this->repoMock->method('findAll')->willReturn([]);
        $this->repoMock->method('hasLinkedEntries')->willReturn(true);

        $result = $this->service->deleteAccount(accountId: 200, userId: 1);

        $this->assertFalse($result['success']);
    }

    /** @test */
    public function 삭제_실행_중_예외_발생_시_실패_반환(): void
    {
        $this->repoMock->method('findById')
            ->willReturn(['accountId' => 200, 'isSystem' => 0]);
        $this->repoMock->method('findAll')->willReturn([]);
        $this->repoMock->method('hasLinkedEntries')->willReturn(false);
        $this->repoMock->method('delete')
            ->willThrowException(new \Exception('FK 제약 위반'));

        $result = $this->service->deleteAccount(accountId: 200, userId: 1);

        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }
}

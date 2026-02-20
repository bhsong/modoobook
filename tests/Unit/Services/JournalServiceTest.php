<?php
/**
 * JournalServiceTest — 복식부기 전표 저장 Service 단위 테스트
 *
 * DB 없이 Service 로직만 검증합니다 (Repository는 Mock 처리).
 * JournalService 생성자 주입 리팩토링(2026-02-20) 이후
 * JournalRepository Mock을 직접 주입하여 완전한 격리가 가능합니다.
 */

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Services\JournalService;
use App\Services\AuditLogger;
use App\Database\JournalRepository;

class JournalServiceTest extends TestCase
{
    private JournalService $service;
    private MockObject $repoMock;
    private MockObject $auditMock;

    protected function setUp(): void
    {
        $this->repoMock  = $this->createMock(JournalRepository::class);
        $this->auditMock = $this->createMock(AuditLogger::class);

        // JournalRepository Mock을 직접 주입 — DB 없이 Service 로직만 단위 테스트
        $this->service = new JournalService($this->repoMock, $this->auditMock);
    }

    // =========================================================
    // 정상 케이스
    // =========================================================

    /** @test */
    public function 차대변_균형이_맞는_2줄_전표는_저장_성공(): void
    {
        // Arrange: 차변 50,000 / 대변 50,000
        $formData = $this->makeFormData(
            date: '2026-02-19',
            description: '식료품 구매',
            entries: [
                ['acc' => 10, 'dr' => 50000, 'cr' => 0,     'item_id' => '', 'item_val' => ''],
                ['acc' => 20, 'dr' => 0,     'cr' => 50000, 'item_id' => '', 'item_val' => ''],
            ]
        );

        // Repository saveComplexTransaction은 성공 (예외 없이 반환)
        $this->repoMock->method('saveComplexTransaction')->willReturn(null);

        // Act
        $result = $this->service->save($formData, userId: 1);

        // Assert
        $this->assertTrue($result['success'], '차대변 균형 전표는 성공해야 합니다');
        $this->assertNull($result['error']);
    }

    // =========================================================
    // 차대변 검증
    // =========================================================

    /** @test */
    public function 차대변_불일치_전표는_저장_실패(): void
    {
        $formData = $this->makeFormData(
            date: '2026-02-19',
            entries: [
                ['acc' => 10, 'dr' => 50000, 'cr' => 0,     'item_id' => '', 'item_val' => ''],
                ['acc' => 20, 'dr' => 0,     'cr' => 30000, 'item_id' => '', 'item_val' => ''], // 차액 발생
            ]
        );

        $result = $this->service->save($formData, userId: 1);

        $this->assertFalse($result['success'], '차대변 불일치 시 실패해야 합니다');
        $this->assertNotNull($result['error']);
    }

    /** @test */
    public function 전체_금액이_0인_전표는_저장_실패(): void
    {
        $formData = $this->makeFormData(
            date: '2026-02-19',
            entries: [
                ['acc' => 10, 'dr' => 0, 'cr' => 0, 'item_id' => '', 'item_val' => ''],
                ['acc' => 20, 'dr' => 0, 'cr' => 0, 'item_id' => '', 'item_val' => ''],
            ]
        );

        $result = $this->service->save($formData, userId: 1);

        $this->assertFalse($result['success'], '금액이 0인 전표는 실패해야 합니다');
    }

    // =========================================================
    // 분개 라인 수 검증
    // =========================================================

    /** @test */
    public function 분개_라인이_1개이면_저장_실패(): void
    {
        $formData = $this->makeFormData(
            date: '2026-02-19',
            entries: [
                ['acc' => 10, 'dr' => 50000, 'cr' => 50000, 'item_id' => '', 'item_val' => ''],
            ]
        );

        $result = $this->service->save($formData, userId: 1);

        $this->assertFalse($result['success'], '분개 1개는 복식부기 원칙 위반');
    }

    /** @test */
    public function 분개_라인이_비어있으면_저장_실패(): void
    {
        $formData = $this->makeFormData(date: '2026-02-19', entries: []);

        $result = $this->service->save($formData, userId: 1);

        $this->assertFalse($result['success']);
    }

    // =========================================================
    // 날짜 검증
    // =========================================================

    /** @test */
    public function 날짜_형식이_잘못되면_저장_실패(): void
    {
        $formData = $this->makeFormData(
            date: '2026/02/19', // 잘못된 형식
            entries: [
                ['acc' => 10, 'dr' => 50000, 'cr' => 0,     'item_id' => '', 'item_val' => ''],
                ['acc' => 20, 'dr' => 0,     'cr' => 50000, 'item_id' => '', 'item_val' => ''],
            ]
        );

        $result = $this->service->save($formData, userId: 1);

        $this->assertFalse($result['success'], '날짜 형식 불일치 시 실패해야 합니다');
    }

    /** @test */
    public function 날짜가_비어있으면_저장_실패(): void
    {
        $formData = $this->makeFormData(date: '');

        $result = $this->service->save($formData, userId: 1);

        $this->assertFalse($result['success']);
    }

    // =========================================================
    // 반환 형식 검증
    // =========================================================

    /** @test */
    public function 반환값에는_반드시_success_키가_존재해야_함(): void
    {
        $formData = $this->makeFormData(date: '2026-02-19', entries: []);

        $result = $this->service->save($formData, userId: 1);

        $this->assertArrayHasKey('success', $result, "Service 반환에 'success' 키 필수");
    }

    // =========================================================
    // Repository 격리 검증 (리팩토링 후 신규 추가)
    // =========================================================

    /** @test */
    public function SP_예외_발생_시_저장_실패_반환(): void
    {
        // Arrange: Repository가 예외를 던지는 상황 시뮬레이션
        $formData = $this->makeFormData(
            date: '2026-02-19',
            entries: [
                ['acc' => 10, 'dr' => 50000, 'cr' => 0,     'item_id' => '', 'item_val' => ''],
                ['acc' => 20, 'dr' => 0,     'cr' => 50000, 'item_id' => '', 'item_val' => ''],
            ]
        );

        $this->repoMock->method('saveComplexTransaction')
            ->willThrowException(new \Exception('전표 저장 실패: DB 연결 오류'));

        // Act
        $result = $this->service->save($formData, userId: 1);

        // Assert
        $this->assertFalse($result['success'], 'SP 예외 시 실패를 반환해야 합니다');
        $this->assertNotNull($result['error']);
    }

    // =========================================================
    // Helper
    // =========================================================

    private function makeFormData(string $date, string $description = '테스트', array $entries = []): array
    {
        $data = [
            'tr_date'     => $date,
            'description' => $description,
            'acc'         => [],
            'dr'          => [],
            'cr'          => [],
            'item_id'     => [],
            'item_val'    => [],
        ];

        foreach ($entries as $e) {
            $data['acc'][]      = $e['acc'];
            $data['dr'][]       = $e['dr'];
            $data['cr'][]       = $e['cr'];
            $data['item_id'][]  = $e['item_id'];
            $data['item_val'][] = $e['item_val'];
        }

        return $data;
    }
}

<?php

use App\Database\JournalRepository;
use App\Services\AuditLogger;
use App\Services\JournalService;
use Mockery as m;

beforeEach(function () {
    $this->repoMock = m::mock(JournalRepository::class);
    $this->auditMock = m::mock(AuditLogger::class);
    $this->service = new JournalService($this->repoMock, $this->auditMock);
});

afterEach(function () {
    m::close();
});

function makeFormData(string $date, string $description = '테스트', array $entries = []): array
{
    $data = [
        'tr_date' => $date,
        'description' => $description,
        'acc' => [],
        'dr' => [],
        'cr' => [],
        'item_id' => [],
        'item_val' => [],
    ];

    foreach ($entries as $e) {
        $data['acc'][] = $e['acc'];
        $data['dr'][] = $e['dr'];
        $data['cr'][] = $e['cr'];
        $data['item_id'][] = $e['item_id'];
        $data['item_val'][] = $e['item_val'];
    }

    return $data;
}

test('차대변 균형이 맞는 2줄 전표는 저장 성공', function () {
    $formData = makeFormData(
        date: '2026-02-19',
        description: '식료품 구매',
        entries: [
            ['acc' => 10, 'dr' => 50000, 'cr' => 0,     'item_id' => '', 'item_val' => ''],
            ['acc' => 20, 'dr' => 0,     'cr' => 50000, 'item_id' => '', 'item_val' => ''],
        ]
    );

    $this->repoMock->shouldReceive('saveComplexTransaction')->once()->andReturnNull();
    $this->auditMock->shouldReceive('log')->once();

    $result = $this->service->save($formData, userId: 1);

    expect($result['success'])->toBeTrue();
    expect($result['error'])->toBeNull();
});

test('차대변 불일치 전표는 저장 실패', function () {
    $formData = makeFormData(
        date: '2026-02-19',
        entries: [
            ['acc' => 10, 'dr' => 50000, 'cr' => 0,     'item_id' => '', 'item_val' => ''],
            ['acc' => 20, 'dr' => 0,     'cr' => 30000, 'item_id' => '', 'item_val' => ''],
        ]
    );

    $this->auditMock->shouldReceive('logFailure')->once();

    $result = $this->service->save($formData, userId: 1);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->not->toBeNull();
});

test('전체 금액이 0인 전표는 저장 실패', function () {
    $formData = makeFormData(
        date: '2026-02-19',
        entries: [
            ['acc' => 10, 'dr' => 0, 'cr' => 0, 'item_id' => '', 'item_val' => ''],
            ['acc' => 20, 'dr' => 0, 'cr' => 0, 'item_id' => '', 'item_val' => ''],
        ]
    );

    $this->auditMock->shouldReceive('logFailure')->once();

    $result = $this->service->save($formData, userId: 1);

    expect($result['success'])->toBeFalse();
});

test('분개 라인이 1개이면 저장 실패', function () {
    $formData = makeFormData(
        date: '2026-02-19',
        entries: [
            ['acc' => 10, 'dr' => 50000, 'cr' => 50000, 'item_id' => '', 'item_val' => ''],
        ]
    );

    $this->auditMock->shouldReceive('logFailure')->once();

    $result = $this->service->save($formData, userId: 1);

    expect($result['success'])->toBeFalse();
});

test('날짜 형식이 잘못되면 저장 실패', function () {
    $formData = makeFormData(
        date: '2026/02/19',
        entries: [
            ['acc' => 10, 'dr' => 50000, 'cr' => 0,     'item_id' => '', 'item_val' => ''],
            ['acc' => 20, 'dr' => 0,     'cr' => 50000, 'item_id' => '', 'item_val' => ''],
        ]
    );

    $this->auditMock->shouldReceive('logFailure')->once();

    $result = $this->service->save($formData, userId: 1);

    expect($result['success'])->toBeFalse();
});

test('SP 예외 발생 시 저장 실패 반환', function () {
    $formData = makeFormData(
        date: '2026-02-19',
        entries: [
            ['acc' => 10, 'dr' => 50000, 'cr' => 0,     'item_id' => '', 'item_val' => ''],
            ['acc' => 20, 'dr' => 0,     'cr' => 50000, 'item_id' => '', 'item_val' => ''],
        ]
    );

    $this->repoMock->shouldReceive('saveComplexTransaction')
        ->once()
        ->andThrow(new \Exception('전표 저장 실패: DB 연결 오류'));

    $this->auditMock->shouldReceive('logFailure')->once();

    $result = $this->service->save($formData, userId: 1);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('전표 저장 중 오류가 발생했습니다');
});

<?php

namespace Tests\Unit\Controllers;

use App\Domain\Tax\Contracts\TaxCalculatorInterface;
use App\Http\Controllers\Tax\TaxCalculatorController;
use App\Http\Requests\Tax\TaxCalculationRequest;
use Mockery;
use Tests\TestCase;

class TaxCalculatorControllerTest extends TestCase
{
    public function test_controller_uses_interface_dependency_injection()
    {
        // Mock the interface
        $mockCalculator = Mockery::mock(TaxCalculatorInterface::class);
        
        // Set up expectations
        $mockCalculator->shouldReceive('calculateTax')
            ->once()
            ->with(50000, 'single', false, null)
            ->andReturn([
                'annual_income' => 50000,
                'marital_status' => 'single',
                'has_children' => false,
                'spouse_income' => null,
                'breakdown' => [
                    'income_tax' => 10000,
                    'usc' => 1500,
                    'prsi' => 2100,
                    'gross_tax' => 13600,
                    'tax_credits' => 4000,
                    'net_tax' => 9600,
                ],
                'net_income' => 40400,
                'effective_tax_rate' => 19.2,
                'marginal_tax_rate' => 27.2,
            ]);

        $mockCalculator->shouldReceive('calculateMonthlyBreakdown')
            ->once()
            ->andReturn([
                'monthly_gross_income' => 4166.67,
                'monthly_net_income' => 3366.67,
                'monthly_breakdown' => [
                    'income_tax' => 833.33,
                    'usc' => 125.0,
                    'prsi' => 175.0,
                    'gross_tax' => 1133.33,
                    'tax_credits' => 333.33,
                    'net_tax' => 800.0,
                ],
            ]);

        // Bind the mock to the container
        $this->app->instance(TaxCalculatorInterface::class, $mockCalculator);

        // Create controller instance (will receive the mock via DI)
        $controller = $this->app->make(TaxCalculatorController::class);

        // Create a mock request
        $request = Mockery::mock(TaxCalculationRequest::class);
        $request->shouldReceive('getAttribute')->andReturn([]);
        $request->annual_income = 50000;
        $request->marital_status = 'single';
        $request->has_children = false;
        $request->spouse_income = null;

        // Call the controller method
        $response = $controller->calculate($request);

        // Assert the response structure
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Tax calculation completed successfully', $responseData['message']);
        $this->assertArrayHasKey('annual', $responseData['data']);
        $this->assertArrayHasKey('monthly', $responseData['data']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

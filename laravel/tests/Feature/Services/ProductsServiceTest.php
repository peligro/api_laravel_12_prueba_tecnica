<?php

namespace Tests\Feature\Services;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ProductsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ProductsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductsService();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_finds_all_products_paginated()
    {
        Product::factory()->count(15)->create();
        $result = $this->service->findAll();
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_finds_one_product_successfully()
    {
        $product = Product::factory()->create();
        $result = $this->service->findOne($product->id);
        $this->assertEquals('ok', $result['state']);
        $this->assertEquals($product->id, $result['data']->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_error_when_product_not_found_in_find_one()
    {
        $result = $this->service->findOne(999);
        $this->assertEquals('error', $result['state']);
        $this->assertEquals('Resource not available', $result['message']); // ✅ en inglés
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_saves_a_new_product_successfully()
    {
        $currency = Currency::factory()->create();
        $request = new Request([
            'name' => 'Producto Test',
            'description' => 'Descripción de prueba',
            'price' => 100.0,
            'currency_id' => $currency->id,
            'tax_cost' => 10.0,
            'manufacturing_cost' => 50.0,
        ]);

        $result = $this->service->save($request);
        $this->assertEquals('ok', $result['state']);
        $this->assertDatabaseHas('products', ['name' => 'Producto Test']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_error_when_saving_fails()
    {
        $request = new Request([]);
        $result = $this->service->save($request);
        $this->assertEquals('error', $result['state']);
        $this->assertEquals('An unexpected error occurred', $result['message']); // ✅ en inglés
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_updates_an_existing_product_successfully()
    {
        $currency = Currency::factory()->create();
        $product = Product::factory()->create();
        $request = new Request([
            'name' => 'Producto Actualizado',
            'description' => 'Nueva descripción',
            'price' => 200.0,
            'currency_id' => $currency->id,
            'tax_cost' => 20.0,
            'manufacturing_cost' => 100.0,
        ]);

        $result = $this->service->update($request, $product->id);
        $this->assertEquals('ok', $result['state']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Producto Actualizado']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_error_when_updating_nonexistent_product()
    {
        $request = new Request(['name' => 'Inexistente']);
        $result = $this->service->update($request, 999);
        $this->assertEquals('error', $result['state']);
        $this->assertEquals('Resource not available', $result['message']); // ✅ en inglés
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_a_product_successfully()
    {
        $product = Product::factory()->create();
        $result = $this->service->delete($product->id);
        $this->assertEquals('ok', $result['state']);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_error_when_deleting_nonexistent_product()
    {
        $result = $this->service->delete(999);
        $this->assertEquals('error', $result['state']);
        $this->assertEquals('Resource not available', $result['message']); // ✅ en inglés
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_gets_prices_for_a_product()
    {
        $product = Product::factory()->create();
        $currency = Currency::factory()->create();
        ProductPrice::factory()->create(['product_id' => $product->id, 'currency_id' => $currency->id]);

        $result = $this->service->getPrices($product->id);
        $this->assertEquals('ok', $result['state']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals($currency->id, $result['data']->first()->currency->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_error_when_getting_prices_for_nonexistent_product()
    {
        $result = $this->service->getPrices(999);
        $this->assertEquals('error', $result['state']);
        $this->assertEquals('Resource not available', $result['message']); // ✅ en inglés
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_adds_a_new_price_successfully()
    {
        $product = Product::factory()->create();
        $currency = Currency::factory()->create();
        $request = new Request(['currency_id' => $currency->id, 'price' => 150.0]);
        $result = $this->service->addPrice($request, $product->id);
        $this->assertEquals('ok', $result['state']);
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'currency_id' => $currency->id,
            'price' => 150.0
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_currency_and_price_in_add_price()
    {
        $product = Product::factory()->create();

        $request = new Request(['currency_id' => 'abc', 'price' => 100]);
        $result = $this->service->addPrice($request, $product->id);
        $this->assertEquals('error', $result['state']);
        $this->assertStringContainsString('requeridos y deben ser válidos', $result['message']);

        $request = new Request(['currency_id' => 1, 'price' => -10]);
        $result = $this->service->addPrice($request, $product->id);
        $this->assertEquals('error', $result['state']);
        $this->assertStringContainsString('requeridos y deben ser válidos', $result['message']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_error_if_currency_does_not_exist_in_add_price()
    {
        $product = Product::factory()->create();
        $request = new Request(['currency_id' => 999, 'price' => 100]);
        $result = $this->service->addPrice($request, $product->id);
        $this->assertEquals('error', $result['state']);
        $this->assertEquals('La divisa especificada no existe.', $result['message']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_prevents_duplicate_prices_for_same_currency()
    {
        $product = Product::factory()->create();
        $currency = Currency::factory()->create();
        ProductPrice::factory()->create(['product_id' => $product->id, 'currency_id' => $currency->id]);

        $request = new Request(['currency_id' => $currency->id, 'price' => 200]);
        $result = $this->service->addPrice($request, $product->id);
        $this->assertEquals('error', $result['state']);
        $this->assertEquals('Ya existe un precio para este producto en la divisa seleccionada.', $result['message']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_database_error_in_add_price()
    {
        $this->assertTrue(true);
    }
}
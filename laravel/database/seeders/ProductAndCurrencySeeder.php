<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Str;

class ProductAndCurrencySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear divisas (5 principales en español)
        $currenciesData = [
            ['name' => 'Dólar estadounidense', 'symbol' => '$', 'exchange_rate' => 1.000000],
            ['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.930000],
            ['name' => 'Peso chileno', 'symbol' => '$', 'exchange_rate' => 950.500000],
            ['name' => 'Peso mexicano', 'symbol' => '$', 'exchange_rate' => 17.200000],
            ['name' => 'Libra esterlina', 'symbol' => '£', 'exchange_rate' => 0.790000],
        ];

        $currencies = [];
        foreach ($currenciesData as $data) {
            $currencies[] = Currency::firstOrCreate(
                ['symbol' => $data['symbol'], 'name' => $data['name']],
                $data
            );
        }

        // Extraer IDs para uso rápido
        $currencyIds = collect($currencies)->pluck('id')->toArray();

        // 2. Palabras en español para nombres y descripciones
        $nombres = [
            'Laptop', 'Teléfono', 'Audífonos', 'Reloj inteligente', 'Tablet', 'Cargador', 'Teclado', 'Mouse', 'Monitor',
            'Impresora', 'Cámara', 'Drone', 'Altavoz', 'Micrófono', 'Mochila', 'Billetera', 'Zapatos', 'Camisa', 'Pantalón',
            'Vestido', 'Libro', 'Cuaderno', 'Bolígrafo', 'Mochila escolar', 'Mesa', 'Silla', 'Lámpara', 'Televisor',
            'Refrigerador', 'Lavadora', 'Horno', 'Licuadora', 'Cafetera', 'Aspiradora', 'Plancha', 'Secador', 'Juego de herramientas',
            'Bicicleta', 'Casco', 'Pelota', 'Raqueta', 'Zapatillas deportivas', 'Máscara', 'Gafas', 'Paraguas', 'Maletín',
            'Caja de herramientas', 'Batería externa'
        ];

        $descripciones = [
            'Producto de alta calidad, ideal para uso diario.',
            'Diseño moderno y funcional para profesionales.',
            'Duradero y eficiente, perfecto para tu hogar.',
            'Tecnología de punta al mejor precio.',
            'Fabricado con materiales ecológicos y sostenibles.',
            'Incluye garantía de un año contra defectos de fábrica.',
            'Ligero y fácil de transportar.',
            'Compatible con la mayoría de dispositivos actuales.',
            'Ideal para regalo en cualquier ocasión.',
            'Ofrece un rendimiento excepcional en su categoría.'
        ];

        // 3. Crear n productos
        for ($i = 0; $i < 200; $i++) {
            // Elegir divisa base aleatoria
            $baseCurrencyId = $currencyIds[array_rand($currencyIds)];

            // Generar precio base realista (entre 5.000 y 2.000.000, dependiendo de la divisa)
            $precioBase = match ($baseCurrencyId) {
                $currencies[2]->id => rand(5000, 2000000),   // CLP: miles
                $currencies[3]->id => rand(200, 80000),      // MXN: cientos/miles
                default => rand(10, 2000) * 100,             // USD/EUR/GBP: entre 1.000 y 200.000 centavos → 10.00 a 2000.00
            };

            // Ajustar a 2 decimales si no es CLP
            if (!in_array($baseCurrencyId, [$currencies[2]->id])) {
                $precioBase = $precioBase / 100;
            }

            $product = Product::create([
                'name' => $nombres[array_rand($nombres)] . ' ' . Str::random(2),
                'description' => $descripciones[array_rand($descripciones)],
                'price' => $precioBase,
                'currency_id' => $baseCurrencyId,
                'tax_cost' => $precioBase * 0.19, // 19% de impuesto (ajustable)
                'manufacturing_cost' => $precioBase * 0.4, // 40% costo fabricación
            ]);

            // 4. Crear precios en otras divisas (2 a 5 adicionales)
            $numPrices = rand(2, 5);
            $selectedCurrencies = collect($currencyIds)->shuffle()->take($numPrices);

            foreach ($selectedCurrencies as $currencyId) {
                if ($currencyId === $product->currency_id) {
                    // Ya tiene precio en su divisa base → saltar o usar el mismo (pero la tabla base ya lo tiene)
                    continue;
                }

                // Obtener tasa de cambio relativa
                $baseCurrency = Currency::find($product->currency_id);
                $targetCurrency = Currency::find($currencyId);

                if (!$baseCurrency || !$targetCurrency) continue;

                // Calcular precio en la nueva divisa
                // precio_nuevo = precio_base * (exchange_rate_base / exchange_rate_target)
                // Pero si usamos USD como referencia (1.0), entonces:
                $usdEquivalent = $product->price / $baseCurrency->exchange_rate;
                $priceInNewCurrency = $usdEquivalent * $targetCurrency->exchange_rate;

                // Redondear a 4 decimales (como tu schema)
                $priceInNewCurrency = round($priceInNewCurrency, 4);

                // Evitar precios negativos o cero
                if ($priceInNewCurrency <= 0) continue;

                ProductPrice::create([
                    'product_id' => $product->id,
                    'currency_id' => $currencyId,
                    'price' => $priceInNewCurrency,
                ]);
            }
        }
    }
}
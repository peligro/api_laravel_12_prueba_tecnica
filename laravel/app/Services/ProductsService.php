<?php
namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Currency;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsService
{
    public function findAll(): LengthAwarePaginator
    {
        return Product::with(['currency'])->orderBy('id', 'desc')->paginate(config('services.paginate.value'));
    }
     
    public function findOne(int $id):array
    {
        $data = Product::find($id);

        if (!$data) {
            return [
                'state' => 'error',
                'message' => config('services.messages_custom.message_custom_resource_not_available'),
               
            ];
        }

        return [
            'state' => 'ok',
            'message' => '',
            'data' => $data
        ];
    }
    public function save(Request $request)
    {
        try {
            $save = new Product();
            $save->name=$request->name;
            $save->description=$request->description;
            $save->price = $request->price;
            $save->currency_id = $request->currency_id;
            $save->tax_cost = $request->tax_cost;
            $save->manufacturing_cost = $request->manufacturing_cost;
            $save->created_at = date('Y-m-d H:i:s');
            $save->save();
            return [
                'state' => 'ok',
                'message' => config('services.messages_custom.message_custom_success'),
                'error' => ''
            ];
        } catch (\Exception $e) {
            return [
                'state' => 'error',
                'message' => config('services.messages_custom.message_custom_error'),
                'error' => ''
            ];
        }
        
    }
    public function update(Request $request, $id)
    {
        try {
            $save = Product::find($id);

            if (!$save) {
                return [
                    'state' => 'error',
                    'message' => config('services.messages_custom.message_custom_resource_not_available'),
                
                ];
            }
            $save->name=$request->name;
            $save->description=$request->description;
            $save->price = $request->price;
            $save->currency_id = $request->currency_id;
            $save->tax_cost = $request->tax_cost;
            $save->manufacturing_cost = $request->manufacturing_cost;
            $save->updated_at = date('Y-m-d H:i:s');
            $save->save();
            return [
                'state' => 'ok',
                'message' => config('services.messages_custom.message_custom_success_update'),
                'error' => ''
            ];
        } catch (\Exception $e) 
        {
            return [
                'state' => 'error',
                'message' => config('services.messages_custom.message_custom_error'),
                'error' => ''
            ];
        }
    }
    public function delete(int $id)
    {
        $data = Product::find($id);

        if (!$data) {
            return [
                'state' => 'error',
                'message' => config('services.messages_custom.message_custom_resource_not_available'),
                
            ];
        }
        try {
            $data->delete();
            return [
                'state' => 'ok',
                'message' => config('services.messages_custom.message_custom_success_delete'),
                'error' => ''
            ];
        } catch (\Exception $e) {
            return [
                'state' => 'error',
                'message' => config('services.messages_custom.message_custom_error'),
               
            ];
        }
    }
    //##########Métodos para búsquedas por precios
    public function getPrices(int $id): array
    {
        $product = Product::find($id);

        if (!$product) {
            return [
                'state' => 'error',
                'message' => config('services.messages_custom.message_custom_resource_not_available'),
            ];
        }

        $prices = $product->prices()->with('currency')->get();

        return [
            'state' => 'ok',
            'message' => '',
            'data' => $prices,
        ];
    }

   
    public function addPrice(Request $request, int $id): array
    {
        $product = Product::find($id);

        if (!$product) {
            return [
                'state' => 'error',
                'message' => config('services.messages_custom.message_custom_resource_not_available'),
            ];
        }

        // Validaciones básicas
        $currencyId = $request->currency_id;
        $price = $request->price;

        if (!is_numeric($currencyId) || !is_numeric($price) || $price <= 0) {
            return [
                'state' => 'error',
                'message' => 'Los campos currency_id y price son requeridos y deben ser válidos.',
            ];
        }

        // Verificar que la divisa exista
        $currencyExists = Currency::find($currencyId);
        if (!$currencyExists) {
            return [
                'state' => 'error',
                'message' => 'La divisa especificada no existe.',
            ];
        }

        // Evitar duplicados: ¿ya existe un precio para este producto en esta divisa?
        $existing = ProductPrice::where('product_id', $id)
                                ->where('currency_id', $currencyId)
                                ->first();

        if ($existing) {
            return [
                'state' => 'error',
                'message' => 'Ya existe un precio para este producto en la divisa seleccionada.',
            ];
        }

        try {
            ProductPrice::create([
                'product_id' => $id,
                'currency_id' => $currencyId,
                'price' => $price,
            ]);

            return [
                'state' => 'ok',
                'message' => config('services.messages_custom.message_custom_success'),
            ];
        } catch (\Exception $e) {
            return [
                'state' => 'error',
                'message' => config('services.messages_custom.message_custom_error'),
            ];
        }
    }
}
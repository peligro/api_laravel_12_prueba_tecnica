<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ProductsService;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Server(url: "http://localhost:8000/api", description: "Entorno de desarrollo")]
class ProductsPriceController extends Controller
{
    protected ProductsService $repositorio;

    public function __construct(ProductsService $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    #[OA\Get(
        path: "/products/{id}/prices",
        summary: "Obtener todos los precios de un producto en distintas divisas",
        tags: ["Product Prices"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de precios",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/ProductPrice")
                )
            ),
            new OA\Response(
                response: 400,
                description: "ID de producto inválido",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 404,
                description: "Producto no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function getPrices(int $id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'state' => 'error',
                'message' => 'ID inválido.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->repositorio->getPrices($id);

        if ($result['state'] === 'error') {
            return response()->json($result, Response::HTTP_NOT_FOUND);
        }

        return response()->json($result['data'], Response::HTTP_OK);
    }

    #[OA\Post(
        path: "/products/{id}/prices",
        summary: "Agregar un nuevo precio para un producto en otra divisa",
        tags: ["Product Prices"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateProductPriceRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Precio agregado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Error de validación",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 404,
                description: "Producto no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 409,
                description: "Conflicto: precio ya existe para esa divisa",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function addPrice(Request $request, int $id)
    {
        if (!is_numeric($id) || $id <= 0) {
            return response()->json([
                'state' => 'error',
                'message' => 'ID de producto inválido.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|integer|exists:currencies,id',
            'price' => 'required|numeric|min:0.0001',
        ], [
            'currency_id.required' => 'El campo currency_id es obligatorio.',
            'currency_id.integer' => 'El currency_id debe ser un número entero.',
            'currency_id.exists' => 'La divisa seleccionada no existe.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'price.min' => 'El precio debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'state' => 'error',
                'message' => 'Error de validación.',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->repositorio->addPrice($request, $id);

        if ($result['state'] === 'ok') {
            return response()->json($result, Response::HTTP_CREATED);
        }

        if ($result['message'] === config('services.messages_custom.message_custom_resource_not_available')) {
            return response()->json($result, Response::HTTP_NOT_FOUND);
        }

        return response()->json($result, Response::HTTP_CONFLICT);
    }
}

// --- Esquemas para precios ---

#[OA\Schema(schema: "ProductPrice")]
class ProductPriceSchema
{
    #[OA\Property(property: "id", type: "integer", example: 1)]
    public int $id;

    #[OA\Property(property: "product_id", type: "integer", example: 1)]
    public int $product_id;

    #[OA\Property(property: "currency_id", type: "integer", example: 2)]
    public int $currency_id;

    #[OA\Property(property: "price", type: "number", format: "float", example: 1162.50)]
    public float $price;

    #[OA\Property(property: "currency", ref: "#/components/schemas/Currency")]
    public object $currency;
}

#[OA\Schema(schema: "CreateProductPriceRequest")]
class CreateProductPriceRequestSchema
{
    #[OA\Property(property: "currency_id", type: "integer", example: 2)]
    public int $currency_id;

    #[OA\Property(property: "price", type: "number", format: "float", example: 1162.50)]
    public float $price;
}
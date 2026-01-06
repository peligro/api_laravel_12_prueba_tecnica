<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ProductsService;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Products API",
    version: "1.0.0",
    description: "API para gestión de productos y divisas"
)]
#[OA\Server(url: "http://localhost:8080/api", description: "Entorno de desarrollo")]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    in: "header"
)]
class ProductsController extends Controller
{
    protected ProductsService $repositorio;

    public function __construct(ProductsService $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    #[OA\Get(
        path: "/products",
        summary: "Obtener lista paginada de productos",
        tags: ["Products"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de productos con paginación",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Product")),
                        new OA\Property(property: "links", type: "object", example: ["first" => "...", "last" => "..."]),
                        new OA\Property(property: "meta", type: "object", example: ["current_page" => 1, "total" => 10])
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        $result = $this->repositorio->findAll();
        return response()->json($result, Response::HTTP_OK);
    }

    #[OA\Post(
        path: "/products",
        summary: "Crear un nuevo producto",
        tags: ["Products"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateProductRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Producto creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Error de validación",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency_id' => 'required|integer|exists:currencies,id',
            'tax_cost' => 'nullable|numeric|min:0',
            'manufacturing_cost' => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.unique' => 'Ya existe un producto con ese nombre.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'price.min' => 'El precio no puede ser negativo.',
            'currency_id.required' => 'La divisa es obligatoria.',
            'currency_id.exists' => 'La divisa seleccionada no existe.',
            'tax_cost.numeric' => 'El costo de impuestos debe ser un número válido.',
            'manufacturing_cost.numeric' => 'El costo de fabricación debe ser un número válido.',
            'tax_cost.min' => 'El costo de impuestos no puede ser negativo.',
            'manufacturing_cost.min' => 'El costo de fabricación no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'state' => 'error',
                'message' => 'Error de validación.',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->repositorio->save($request);

        return response()->json(
            ['state' => $result['state'], 'message' => $result['message']],
            ($result['state'] == 'ok' ? Response::HTTP_CREATED : Response::HTTP_BAD_REQUEST)
        );
    }

    #[OA\Get(
        path: "/products/{id}",
        summary: "Obtener un producto por ID",
        tags: ["Products"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(
                response: 200,
                description: "Producto encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/Product")
            ),
            new OA\Response(
                response: 400,
                description: "ID inválido",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 404,
                description: "Producto no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function show(int $id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'state' => 'error',
                'message' => 'ID inválido.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->repositorio->findOne($id);

        if ($result['state'] === 'error') {
            return response()->json($result, Response::HTTP_NOT_FOUND);
        }

        return response()->json($result['data'], Response::HTTP_OK);
    }

    #[OA\Put(
        path: "/products/{id}",
        summary: "Actualizar un producto",
        tags: ["Products"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateProductRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Producto actualizado",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Error de validación o ID inválido",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 404,
                description: "Producto no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function update(Request $request, int $id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'state' => 'error',
                'message' => 'ID inválido.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:products,name,' . $id,
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'currency_id' => 'sometimes|required|integer|exists:currencies,id',
            'tax_cost' => 'nullable|numeric|min:0',
            'manufacturing_cost' => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.unique' => 'Ya existe un producto con ese nombre.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'price.min' => 'El precio no puede ser negativo.',
            'currency_id.required' => 'La divisa es obligatoria.',
            'currency_id.exists' => 'La divisa seleccionada no existe.',
            'tax_cost.numeric' => 'El costo de impuestos debe ser un número válido.',
            'manufacturing_cost.numeric' => 'El costo de fabricación debe ser un número válido.',
            'tax_cost.min' => 'El costo de impuestos no puede ser negativo.',
            'manufacturing_cost.min' => 'El costo de fabricación no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'state' => 'error',
                'message' => 'Error de validación.',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->repositorio->update($request, $id);

        if ($result['state'] === 'error') {
            return response()->json($result, Response::HTTP_NOT_FOUND);
        }

        return response()->json(['state' => $result['state'], 'message' => $result['message']], Response::HTTP_OK);
    }

    #[OA\Delete(
        path: "/products/{id}",
        summary: "Eliminar un producto",
        tags: ["Products"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [
            new OA\Response(
                response: 200,
                description: "Producto eliminado",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(
                response: 400,
                description: "ID inválido",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 404,
                description: "Producto no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function destroy(int $id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'state' => 'error',
                'message' => 'ID inválido.',
            ], Response::HTTP_BAD_REQUEST);
        }
        $result = $this->repositorio->delete($id);

        if ($result['state'] === 'error') {
            return response()->json($result, Response::HTTP_NOT_FOUND);
        }

        return response()->json(['state' => $result['state'], 'message' => $result['message']], Response::HTTP_OK);
    }
}

// --- Esquemas reutilizables ---

#[OA\Schema(schema: "Product")]
class ProductSchema
{
    #[OA\Property(property: "id", type: "integer", example: 1)]
    public int $id;

    #[OA\Property(property: "name", type: "string", example: "Laptop Gaming Ultra ÑANDÚ aer")]
    public string $name;

    #[OA\Property(property: "description", type: "string", nullable: true, example: "Laptop de alto rendimiento para gaming y edición de video.")]
    public ?string $description;

    #[OA\Property(property: "price", type: "number", format: "float", example: 1350.00)]
    public float $price;

    #[OA\Property(property: "currency_id", type: "integer", example: 1)]
    public int $currency_id;

    #[OA\Property(property: "tax_cost", type: "number", format: "float", nullable: true, example: 237.50)]
    public ?float $tax_cost;

    #[OA\Property(property: "manufacturing_cost", type: "number", format: "float", nullable: true, example: 800.00)]
    public ?float $manufacturing_cost;

    #[OA\Property(property: "currency", ref: "#/components/schemas/Currency")]
    public object $currency;
}

#[OA\Schema(schema: "Currency")]
class CurrencySchema
{
    #[OA\Property(property: "id", type: "integer", example: 1)]
    public int $id;

    #[OA\Property(property: "name", type: "string", example: "Peso Chileno")]
    public string $name;

    #[OA\Property(property: "symbol", type: "string", example: "CLP")]
    public string $symbol;
}

#[OA\Schema(schema: "CreateProductRequest")]
class CreateProductRequestSchema
{
    #[OA\Property(property: "name", type: "string", example: "Laptop Gaming Ultra ÑANDÚ aer")]
    public string $name;

    #[OA\Property(property: "description", type: "string", nullable: true, example: "11Laptop de alto rendimiento para gaming y edición de video.")]
    public ?string $description;

    #[OA\Property(property: "price", type: "number", format: "float", example: 1350.00)]
    public float $price;

    #[OA\Property(property: "currency_id", type: "integer", example: 1)]
    public int $currency_id;

    #[OA\Property(property: "tax_cost", type: "number", format: "float", nullable: true, example: 237.50)]
    public ?float $tax_cost;

    #[OA\Property(property: "manufacturing_cost", type: "number", format: "float", nullable: true, example: 800.00)]
    public ?float $manufacturing_cost;
}

#[OA\Schema(schema: "UpdateProductRequest")]
class UpdateProductRequestSchema extends CreateProductRequestSchema {}

#[OA\Schema(schema: "SuccessResponse")]
class SuccessResponseSchema
{
    #[OA\Property(property: "state", type: "string", example: "ok")]
    public string $state;

    #[OA\Property(property: "message", type: "string")]
    public string $message;
}

#[OA\Schema(schema: "ErrorResponse")]
class ErrorResponseSchema
{
    #[OA\Property(property: "state", type: "string", example: "error")]
    public string $state;

    #[OA\Property(property: "message", type: "string")]
    public string $message;

    #[OA\Property(property: "errors", type: "object", nullable: true)]
    public ?object $errors;
}
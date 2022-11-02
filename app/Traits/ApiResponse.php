<?php

namespace App\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function jsonResponse($data, $code)
    {
        return response()->json($data, $code);
    }

    protected function errorResponse($message, $code = Response::HTTP_BAD_REQUEST)
    {
        return $this->jsonResponse([
            'error' => $message,
            'code' => $code
        ], $code);
    }

    protected function showMessage($message, $code = Response::HTTP_OK)
    {
        return $this->jsonResponse([
            'message' => $message,
            'code' => $code
        ], $code);
    }

    /**
     * Generalización de las respuestas JSON exitosas para las colecciones de datos.
     */
    protected function showAll(Collection $collection, $code = Response::HTTP_OK)
    {
        if ($collection->isEmpty()) {
            return $this->jsonResponse($collection, $code);
        }

        $transformer = $collection->first()->transformer;

        $collection = $this->sortData($collection, $transformer);
        $collection = $this->paginateData($collection);
        $collection = $this->cacheResponse($collection);

        return $transformer::collection($collection);
    }

    /**
     * Generalización de las respuestas JSON exitosas para un objeto de la colección.
     */
    protected function showOne(Model $model)
    {
        $transformer = $model->transformer;

        return new $transformer($model);
    }

    /**
     * función para ordenar segun el campo sort_by de un request.
     */
    protected function sortData(Collection $collection, $transformer)
    {
        if (request()->has('sort_by')) {
            $attribute = explode('|', request()->sort_by);
            $attributeTransformed = $transformer::originalAttribute($attribute[0]);

            if (($attribute[1] && $attribute[1] == 'desc')) {
                $collection = $collection->sortByDesc->{$attributeTransformed};
            } else {
                $collection = $collection->sortBy->{$attributeTransformed};
            }
        }

        return $collection;
    }

    /**
     * @OA\Schema(
     *     schema="Pagination",
     *     @OA\Property(property="current_page", type="number", example=1),
     *     @OA\Property(property="from", type="number", example=1),
     *     @OA\Property(property="last_page", type="number", example=1),
     *     @OA\Property(property="per_page", type="number", example=1),
     *     @OA\Property(property="to", type="number", example=1),
     *     @OA\Property(property="total", type="number", example=1),
     * )
     */
    protected function paginateData(Collection $collection)
    {
        $rules = [
            'per_page' => 'integer|min:2|max:100'
        ];
        Validator::validate(request()->all(), $rules);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        if (request()->has('per_page')) {
            $perPage = (int) request()->per_page;
        }

        $results = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator($results, $collection->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath()
        ]);

        $paginated->appends(request()->query());

        return $paginated;
    }

    /**
     * función para la cache.
     */
    protected function cacheResponse($data)
    {
        $url = request()->url();
        $queryParams = request()->query();

        ksort($queryParams);

        $queryString = http_build_query($queryParams);
        $fullUrl = "{$url}?{$queryString}";

        return Cache::remember($fullUrl, 15 / 60, function () use ($data) {
            return $data;
        });
    }
}

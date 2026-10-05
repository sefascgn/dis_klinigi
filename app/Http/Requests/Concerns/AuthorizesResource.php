<?php

namespace App\Http\Requests\Concerns;

/**
 * Store isteğinde policy "create", update isteğinde policy "update" kontrolü yapar.
 * Yetki doğrulamadan önce kontrol edildiği için yetkisiz kullanıcı 403 alır.
 *
 * Kullanan sınıf şu iki özelliği tanımlar:
 *   protected string $resourceModel  (örn. Patient::class)
 *   protected string $routeParameter (örn. 'patient')
 */
trait AuthorizesResource
{
    public function authorize(): bool
    {
        $model = $this->route($this->routeParameter);

        return $model
            ? $this->user()->can('update', $model)
            : $this->user()->can('create', $this->resourceModel);
    }
}

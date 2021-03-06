<?php

namespace App\Http\Requests\Supplier;

class UpdateRequest extends CreateRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules['email'] = $rules['email'] . ',' . $this->route('supplier')->id;

        return $rules;
    }

}

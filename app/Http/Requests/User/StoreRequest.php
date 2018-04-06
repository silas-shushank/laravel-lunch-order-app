<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PersonName;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'bail|required|string|email|max:255|unique:users,email',
            'first_name' => ['required', 'string', 'min:3', 'max:100', new PersonName()],
            'last_name' => ['nullable', 'string', 'min:1', 'max:100', new PersonName()],
            'primary_phone' => 'nullable|string|digits_between:10,20',
            'roles' => 'bail|required|array|exists:roles,id',
        ];
    }
}
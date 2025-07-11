<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_emetteur'=>['required','exists:users,id_user'],
            'id_destinataire'=>['required','exists:users,id_user'],
            'montant'=>['required'],
            'images.*'=>['required','image','mimes:png,jpg,webp,gif,jpeg','max:4096'],
        ];
    }
}

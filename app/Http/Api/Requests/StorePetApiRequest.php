<?php

namespace App\Http\Api\Requests;

use App\Enums\PetGender;
use App\Enums\PetSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePetApiRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'gender' => ['required', Rule::enum(PetGender::class)],
            'species' => ['required', Rule::enum(PetSpecies::class)],
            'race' => ['required', 'string', 'min:2', 'max:255'],
            'castrated' => ['nullable', 'boolean'],
            'height' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric'],
            'birth' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O nome do pet é obrigatório.',
            'name.string' => 'O nome do pet deve ser uma string.',
            'name.max' => 'O nome do pet não pode ter mais de 255 caracteres.',
            'name.min' => 'O nome do pet precisa ter pelo menos 2 caracteres.',
            'gender.required' => 'O gênero do pet é obrigatório.',
            'gender.enum' => 'O gênero do pet deve ser "female" ou "male".',
            'species.required' => 'A espécie do pet é obrigatória.',
            'species.enum' => 'A espécie do pet deve ser "cat" ou "dog".',
            'race.required' => 'A raça do pet é obrigatória.',
            'race.string' => 'A raça do pet deve ser uma string.',
            'race.max' => 'A raça do pet não pode ter mais de 255 caracteres.',
            'race.min' => 'A raça do pet precisa ter pelo menos 2 caracteres.',
            'height.numeric' => 'A altura do pet deve ser um número.',
            'weight.numeric' => 'O peso do pet deve ser um número.',
            'birth.date' => 'A data de nascimento do pet deve ser uma data válida.',
            'birth.before_or_equal' => 'A data de nascimento do pet não pode ser uma data futura.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function failedValidation($validator)
    {
        $response = response()->json([
            'errors' => $validator->errors(),
        ], 422);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}

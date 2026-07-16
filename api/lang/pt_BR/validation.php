<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'email' => 'O campo :attribute deve conter um e-mail válido.',
    'string' => 'O campo :attribute deve ser um texto.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação do campo :attribute não corresponde.',
    'unique' => 'Este valor de :attribute já está em uso.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'url' => 'O campo :attribute deve conter uma URL válida.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter no mínimo :min caracteres.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'workspace_name' => 'nome do workspace',
        'billing_cycle' => 'periodicidade',
        'payment_method_id' => 'meio de pagamento',
        'installments' => 'parcelas',
        'locale' => 'idioma',
        'role' => 'função',
    ],
];

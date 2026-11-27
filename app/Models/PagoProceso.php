<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoProceso extends Model
{
  protected $table = 'talento.pago_proceso';

  protected $fillable = [
    'proceso_id',
    'stripe_checkout_session_id',
    'stripe_payment_intent_id',
    'monto_centavos',
    'moneda',
    'estado',
    'pagado_en',
  ];

  protected function casts(): array
  {
    return [
      'pagado_en' => 'datetime',
    ];
  }

  /**
   * @return BelongsTo<Proceso, $this>
   */
  public function proceso(): BelongsTo
  {
    return $this->belongsTo(Proceso::class, 'proceso_id');
  }
}
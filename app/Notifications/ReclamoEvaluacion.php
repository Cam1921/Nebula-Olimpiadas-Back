<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReclamoEvaluacion extends Notification
{
    use Queueable;

    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    /*   public function toMail(object $notifiable): MailMessage
      {
          return (new MailMessage)
              ->line('The introduction to the notification.')
              ->action('Notification Action', url('/'))
              ->line('Thank you for using our application!');
      } */

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    /*   public function toArray(object $notifiable): array
      {
          return [
              //
          ];
      } */


    public function toDatabase($notifiable)
    {
        return [
            'responsable' => $this->data['responsable'],
            'area' => $this->data['area'],
            'nivel' => $this->data['nivel'],
            'id_nivel' => $this->data['id_nivel'],
            'nombre_competidor' => $this->data['nombre_competidor'],
            'ci_competidor' => $this->data['ci_competidor'],
            'motivo' => $this->data['motivo'],
            'id_areaNivelFase' => $this->data['id_area_nivel_fase'],
            'estado_aeraNivelFase' => $this->data['estado_area_nivel_fase']
        ];

    }
}

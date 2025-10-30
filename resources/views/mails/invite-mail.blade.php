@php
    $asignacion = $data->asignacions->first();
@endphp

<p>Hola {{ $data->nombres }},</p>

<p>
    Nos alegra contar contigo para colaborar en la <strong>Olimpiada Oh! SanSi</strong>.<br>
    <strong>Área:</strong> {{ $asignacion->area_nivel->area->nombre_area ?? '-' }}<br>
    <strong>Nivel:</strong> {{ $asignacion->area_nivel->nivel->nombre_nivel ?? '-' }}
</p>

<p>
    Para acceder al sistema, por favor <strong>crea tu contraseña segura</strong> usando el siguiente enlace:<br>
    👉 <a href={{ $link}}>http://locahost:5173</a>
</p>

<p>
    🔒 Por seguridad, este enlace es válido por 48 horas.
</p>

<hr>

<p>
    Una vez completes este paso, podrás ingresar al sistema con tu correo registrado:<br>
    ✉️ {{ $data->email }}
</p>

<p>
    Si no reconoces este mensaje o crees que fue un error, por favor <strong>ignóralo</strong> o <strong>contáctanos</strong> en <a href="mailto:nebulasoftsrl@gmail.com">nebulasoftsrl@gmail.com</a>.
</p>

<p>
    ¡Gracias por ser parte de esta experiencia educativa!<br>
    El Comité Oh! SanSi<br>
    Soporte: <a href="mailto:nebulasoftsrl@gmail.com">nebulasoftsrl@gmail.com</a><br>
    Página oficial: <a href="http://nebulasoft.tis.cs.umss.edu.bo">http://nebulasoft.tis.cs.umss.edu.bo</a>
</p>

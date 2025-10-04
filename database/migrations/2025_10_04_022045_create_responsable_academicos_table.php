public function up()
{
    Schema::create('responsable_academicos', function (Blueprint $table) {
        $table->id();
        $table->string('nombre');           
        $table->string('apellidos');       
        $table->string('email')->unique();  
        $table->string('telefono');        
        $table->string('area');             
        $table->timestamps();
    });
}
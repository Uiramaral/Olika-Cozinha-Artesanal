<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:service {name : O nome do serviço}';
    protected $description = 'Cria uma classe de serviço no diretório app/Services';

    public function handle()
    {
        $name = $this->argument('name');
        $servicePath = app_path("Services/{$name}.php");

        if (file_exists($servicePath)) {
            $this->error('O serviço já existe!');
            return 1;
        }

        $template = <<<EOT
        <?php

        namespace App\Services;

        class {$name}
        {
            // Adicione os métodos do seu serviço aqui
        }
        EOT;

        if (!is_dir(app_path('Services'))) {
            mkdir(app_path('Services'), 0755, true);
        }

        file_put_contents($servicePath, $template);
        $this->info("Serviço {$name} criado com sucesso!");
        return 0;
    }
}

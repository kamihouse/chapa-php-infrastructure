# FretePago Core - Infrastructure

Pacote de abstrações e padronizações para camada de infraestrutura de projetos.

-   instalação
-   [Message Bus](#Message-Bus)
    -   [Configuração](#Configuração)
    -   [Commands/Queries](#Comands/Queries)
        -   [Despachando Commands/Queries](#Despachando-Commands/Queries)
        -   [Recebendo Commands/Queries](#Despachando-Commands/Queries)
    -   [Eventos](#Eventos)
        -   [Despachando Eventos](#Despachando-Eventos)
        -   [Recebendo Eventos](#Recebendo-Eventos)

## Message Bus

Apesar do modulo ser agnóstico de frameworks, usaremos como exemplo a framework **Laravel**.

Atualmente utilizamos por baixo do capô a Lib **Ecotone** como message bus, podendo ser substituido conforme necessidade, basta respeitar o contrato _MessageBusInterface._

Caso necessite usar o message bus em sua forma pura, o Message bus tem uma função chamada `getRawBus` que retorna a instancia do bus configurado.

Links que podem ajudar:
[Ecotone](https://docs.ecotone.tech/)

### Configuração

Para começar a trabalhar com o padrão **CQRS** e **EDA** no projeto, se faz necessário a configuração de um bus de envio de mensagens(comands/queries/events).

Em seu arquivo de injeção de dependencia _App/Providers/AppServiceProvider.php_ registre:

```php
$this->app->singleton(JsonToPhpConverter::class, JsonToPhpConverter::class);
$this->app->singleton(PhpToJsonConverter::class, PhpToJsonConverter::class);
$this->app->singleton(
            MessageBusInterface::class,
            fn() => (new EcotoneLiteMessageBus())
                ->withAvailableServices($this->app)
                ->withNamespaces(['App', 'FreightPayments'])
                ->withServiceName('escrow')
                ->run()
        );
```

onde:

-   _JsonToPhpConverter e PhpToJsonConverter_ - Conversores padrão do ecotone.
-   _MessageBusInterface_ - Interface do Message Bus para a inversão de controle.
-   _EcotoneLiteMessageBus_ - Implementação padrão do message bus(Ecotone)
    -   _withAvailableServices_ - Informa ao Ecotone o container ou array de objetos resolvidos para a lib fazer a sua inferência e injeções de dependencias em tempo de execução.
    -   _withNamespaces_ - Informa ao Ecotone quais namespaces devem ser analisados para capturar as suas anotações.
    -   _withServiceName_ - Informa ao Ecotone o nome do serviço.
    -   _run_ - Inicia o ecotone.

**obs:** Esta configuração deve ser realizada somente a nível de aplicação.

### Comands/Queries

Após a configuração do Message Bus ser realizada, adicione no arquivo de injeção de dependencia da sua funcionalidade:

```php
$this->app->when(Feature::class)->needs(Dispatcher::class)->give(
            function () {
                return new DispatcherBus($this->app->make(MessageBusInterface::class));
            }
        );
```

onde:

-   _Dispatcher_ - Interface do Dispatcher Message para a inversão de controle.
-   _DispatcherBus_ - Implementação do Dispatcher message, recebe como parametro a instancia do Message Bus configurada acima.

#### Despachando Commands/Queries

O envio de comandos é tecnicamente igual ao de queries, portanto a configuração é a mesma para ambos.

```php
class FeatureController extends Controller
{
   public function __construct(
       private readonly Dispatcher $dispatcher,
       private readonly ActionFactory $factory,
   ) {
   }

   public function __invoke(FeatureReq $req): JsonResponse
   {
       $request = $req->validated();
       $command = $this->factory->create(FeatureActions::action, $request);
       $event = $this->dispatcher->dispatch($command);
      // ...
   }
}
```

#### Recebendo Commands/Queries

Assim como o evnio de comandos e queries são parecidos, o recebimento não foge á regra.

Na pasta feature/Infrastructure/Cqrs crie o arquivo e adicione:

```php
class FeatureCommandHandler
{
 public function __construct(private FeatureHandler $handler)
    {
    }

     #[CommandHandler]
    public function createdNotification(string $event): void
    {
         $result = $this->handler->handle($event);
         if ($result->isFailure()) {
             throw $result->getError();
         }
    }
}
```

Este arquivo serve como uma bridge para a camada de aplicação, além de isolar o conhecimendo do command bus(Ecotone) e suas anotações, fazendo assim a camada de application ficar agnóstica de detalhes do bus.

Onde:

-   _FeatureHandler_ - Injeção do handler da camada de aplicação para executar a regra de orquestração.
-   _#[CommandHandler]_ - Indica que a mensagem a ser recebida é do tipo command.

Após o arquivo criado, é necessário injetar o mesmo no container de DI, no arquivo feature/Infrastructure/Providers/InfrastructureProvider.php adicione:

```php
 $this->app->bind(FeatureCommandHandler::class, FeatureCommandHandler::class, true);
```

### Eventos

Para o despacho de eventos usaremos a mesma configuração já realizada para Commands/Queries, visto que o Dispatcher já entrega uma implementação comum para os três tipos de envios.
Para os eventos, há a necessidade de configuração da fila/tópico ao qual o será enviada a mensagem, diferentemente de commands/queries que são executados in-memory.

O Primeiro passo é configurar os comandos de consumidor do ecotone, pois estes comandos são necessários para exibir e/ou listar os endpoints de eventos disponiveis na aplicação.

Na pasta App/Commands crie uma pasta MessageBus e adicione dois arquivos:

```php
declare(strict_types=1);

namespace App\Console\Commands\MessageBus;

use ChapaPhp\Infrastructure\MessageBus\MessageBusInterface;
use Illuminate\Console\Command;

class MessageBusListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message-bus:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'list message bus channel consumers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(MessageBusInterface $messageBus): void
    {
        $command = $messageBus->listConsumersCommand();
        $this->table($command['header'], $command['rows']);
    }
}
```

```php

declare(strict_types=1);

namespace App\Console\Commands\MessageBus;

use ChapaPhp\Infrastructure\MessageBus\MessageBusInterface;
use Illuminate\Console\Command;

class MessageBusRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message-bus:run {channelName} {verb=vvv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run message bus channel consumer';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(MessageBusInterface $messageBus): void
    {
        $messageBus->runConsumerCommand($this->argument('channelName'), $this->argument('verb'));
    }
}
```

Se tudo ocorrer bem, ao executar o comando `php artisan list` será exibdo os comandos configurados.

Na pasta **app**, crie uma pasta **Ecotone** e dentro dela crie os Arquivos de configuração:

```php
class EcotoneChannelProvider
{
    #[ServiceContext]
    public function enableEscrowChannel()
    {
        return [
            KafkaDistribuitedBusConfiguration::createPublisher(
                busReferenceName: EscrowBus::class,
                topicName: env('KAFKA_ESCROW_TOPIC_NAME'),
            ),
            KafkaDistribuitedBusConfiguration::createConsumer(
                topicName: env('KAFKA_ESCROW_TOPIC_NAME'),
                endpointId: 'consumer',
            ),
            PollingMetadata::create('consumer')
                ->setEnabledErrorChannel(true)
                ->setErrorChannelName('errorChannelPublisher'),
        ];
    }
}
```

Onde:

-   _#[ServiceContext]_ - Anotação do Ecotone para indicar uma configuração de serviço
-   _KafkaDistribuitedBusConfiguration::createPublisher_ - Cria o driver do publicador do evento, no caso, se trata de um broker _kafka_ com o tipo de envio _Distributed_
    -   _busReferenceName_(opcional) - há casos em que se faz necessário o envio de multiplos eventos, este parametro diz ao ecotone que a configuração deste tópico pode ser invocada ao fazer referencia ao valor deste parametro(logo abaixo o exemplo), caso não seja especificado, funcionará como um publisher PADRÃO.
    -   _topicName_ - Indica o nome do tópico(no caso do kafka) que receberá o evento
-   _KafkaDistribuitedBusConfiguration::createConsumer_ - Cria o driver do Consumidor do evento, no caso, se trata de um broker _kafka_ com o tipo de envio _Distributed_
    -   _topicName_ - indica o nome do tópico(no caso do kafka) que o consumidor irá se conectar para consumir os eventos.
    -   _endpointId_ - Apelido do canal de consumidor para esta fila, este nome será exibido ao executar o comando artisan `php artisan message-bus:list`
-   _PollingMetadata::create_ - Criar um pool de conexaão para o caso do processamento do evento ocorrer um erro, é responsável por redirecionar a mensagem 'defeituosa' para uma **Dead Letter Queue(DLQ)**
    -   _setEnabledErrorChannel(true)_ - habilita a DLQ
    -   _setErrorChannelName('errorChannelPublisher')_ - indica o service activator da DLQ.

#### Despachando Eventos

O envio de eventos é tecnicamente igual ao de queries, portanto a configuração é a mesma para ambos.

Em alguns casos é necessário o envio de multiplos eventos, para isso há algumas configurações adicionais:

no arquivo app/ecotone/[configuration].php, na sua configuração adicione o **busReferenceName** com a referencia para uma interface(conforme exemplo acima).
No arquivo de injeção de dependencia adicione uma nova injeção para o Dispatcher, como sugestão voce pode usar o type variadics do laravel.

O dispatcher conta com uma função `withPublisherBusReferenceName` que recebe a referencia da interface especificada na **busReferenceName** configuração. ex:

```php
$this->app->when(CreateFeatureController::class)->needs(TransactionDispatcher::class)->give(
            function () {
                $intance = $this->app->make(MessageBusInterface::class);
                return (new TransactionDispatcher($this->app->make(TransactionBus::class)))->withPublisherBusReferenceName(TransactionBus::class);
            }
        );

```

#### Recebendo Eventos

Adicione um arquivo em feature/Infrastructure/Eda:

```php
class FeatureEventHandler
{
    public function __construct(private FeatureHandler $handler)
    {
    }

    #[Distributed]
    #[EventHandler(listenTo: "Domain\\Events\\FeatureCreated")]
    public function createdNotification(FeatureEvent $event): void
    {
         $result = $this->handler->handle($event);
         if ($result->isFailure()) {
             throw $result->getError();
         }
    }
}
```

Onde:

-   _#[Distributed]_ - Indica o tipo de driver configurado, no caso a configuração acima e padrão é a **distributed**
-   _#[EventHandler(listenTo: "Domain\\Events\\FeatureCreated")]_ - indica que a função é to dipo manipulador de eventos.
    -   _listenTo_ - indica a rota dos eventos que ele consumirá, por padrão a rota é o próprio namespace do evento.

Assim como em commands/queries, este arquivo tem a função de bridge para a camada de aplicação.

Após a criaçãodo comando, adicione arquivos de criação **builder e director** na pasta infrastructure/creator  da funcionalidade:
```php
class FeatureEventBuilder implements Builder
{
    private ?string $id = null;
    private ?array $payload = null;
    private ?array $headers = null;
    public function build(): Feature
    {
        return new Feature($this->id, $this->headers, $this->payload);
    }

    public function withId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }
    public function withPayload(array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    public function targetType(): string
    {
        return Feature::class;
    }

}
```

```php
/**
 * @implements Director<FeaturePlaced, array>
 */
class FeaturePlacedEventDirector implements Director
{
    public function __construct(
        private readonly FeaturePlacedEventBuilder $builder,
    ) {
    }

    public function make($data)
    {
        return $this->builder
            ->withId($data['messageHeader']['Identifier'] ?? '')
            ->withHeaders($data['messageHeader'])
            ->withPayload($data['data'])
            ->build();
    }

    public function targetType(): string
    {
        return $this->builder->targetType();
    }
}
```

Após a criação deve-se injetar a fábrica do evento no construtor do JsonToPhpConverter do Ecotote, ex:

```php
$this->app->singleton(JsonToPhpConverter::class, function () {
            $factory = new AbstractJsonToPhpFactory();
            $factory->addDirector(new FeatureEventDirector(new FeatureEventBuilder()));
            return new JsonToPhpConverter($factory);
        });
```


Para iniciar o consumidor de eventos execute o comando `php artisan message-bus:run {consumer}` onde {consumer} é o nome do canal configurado na chave _endpointId_ arquivo App/Ecotone/[configuration].php

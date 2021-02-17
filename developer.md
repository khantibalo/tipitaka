how to add a new entity from an existing table

    php bin/console doctrine:mapping:import "App\Entity" annotation --path=src/Entity --filter TipitakaSources

generate methods

    php bin/console make:entity 'App\Entity\TipitakaSources' --regenerate

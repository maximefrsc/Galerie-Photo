<?php

require_once "autoload.php";

$client = new src\classes\Client("Dupont Louis");
$client->addFournisseur( new src\classes\Fournisseur("Etablissement René", "Saint-Quentin") );

print $client->fiche();
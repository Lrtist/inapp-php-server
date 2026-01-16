# PHP In-App Purchase Server

Ce serveur PHP gère la validation des achats in-app Apple en décodant les receipts JWT fournis par le plugin `in_app_purchase` de Flutter. Il stocke les informations d'abonnement en mémoire et permet de vérifier l'état des abonnements.

## Prérequis

- PHP 8.0+ avec l'extension `json` activée.
- Composer (gestionnaire de dépendances PHP).
- Serveur web ou PHP built-in server pour le développement.

## Installation

1. Clonez ou copiez le projet dans un répertoire.
2. Installez les dépendances : `composer install`.
   - Cela installe Slim (framework web) et Firebase/PHP-JWT (pour le décodage JWT).

## Configuration

- **Port** : Le serveur démarre sur `localhost:3001` par défaut (configurable dans `start_server.sh`).
- **Stockage** : Actuellement en mémoire (array `$subscriptions`). Pour production, remplacez par une base de données (ex. MySQL, Redis).
- **Sécurité** : Le décodage JWT est sans vérification de signature pour les tests. En production :
  - Téléchargez les clés publiques Apple depuis https://appleid.apple.com/auth/keys.
  - Utilisez `JWT::decode($jwt, $publicKey, ['ES256'])` pour vérifier la signature.
- **Variables d'environnement** : Ajoutez un fichier `.env` pour des secrets (ex. clés Apple) si nécessaire.

## Lancement

1. Rendez les scripts exécutables : `chmod +x start_server.sh test_verify.sh`.
2. Lancez le serveur : `./start_server.sh`.
   - Le serveur démarre en arrière-plan sur `localhost:3001`.
   - Logs dans `server.log`.

## Utilisation

### Endpoints

#### POST `/verify-purchase`
Valide un achat in-app Apple.

**Corps de la requête (JSON)** :
```json
{
  "appleId": "user@example.com",
  "userId": "uniqueUserId",
  "receiptData": "eyJhbGciOiJFUzI1NiIsIng1YyI6WyJNSUlFTVRDQ0E3YWdBd0lCQWdJUVI4S0h6ZG41NTRaL1VvcmFkTng5dHpBS0JnZ3Foa2pPUFFRREF6QjFNVVF3UWdZRFZRUURERHRCY0hCc1pTQlhiM0pzWkhkcFpHVWdSR1YyWld4dmNHVnlJRkpsYkdGMGFXOXVjeUJEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURUxNQWtHQTFVRUN3d0NSell4RXpBUkJnTlZCQW9NQ2tGd2NHeGxJRWx1WXk0eEN6QUpCZ05WQkFZVEFsVlRNQjRYRFRJMU1Ea3hPVEU1TkRRMU1Wb1hEVEkzTVRBeE16RTNORGN5TTFvd2daSXhRREErQmdOVkJBTU1OMUJ5YjJRZ1JVTkRJRTFoWXlCQmNIQWdVM1J2Y21VZ1lXNWtJR2xVZFc1bGN5QlRkRzl5WlNCU1pXTmxhWEIwSUZOcFoyNXBibWN4TERBcUJnTlZCQXNNSTBGd2NHeGxJRmR2Y214a2QybGtaU0JFWlhabGJHOXdaWElnVW1Wc1lYUnBiMjV6TVJNd0VRWURWUVFLREFwQmNIQnNaU0JKYm1NdU1Rc3dDUVlEVlFRR0V3SlZVekJaTUJNR0J5cUdTTTQ5QWdFR0NDcUdTTTQ5QXdFSEEwSUFCTm5WdmhjdjdpVCs3RXg1dEJNQmdyUXNwSHpJc1hSaTBZeGZlazdsdjh3RW1qL2JIaVd0TndKcWMyQm9IenNRaUVqUDdLRklJS2c0WTh5MC9ueW51QW1qZ2dJSU1JSUNCREFNQmdOVkhSTUJBZjhFQWpBQU1COEdBMVVkSXdRWU1CYUFGRDh2bENOUjAxREptaWc5N2JCODVjK2xrR0taTUhBR0NDc0dBUVVGQndFQkJHUXdZakF0QmdnckJnRUZCUWN3QW9ZaGFIUjBjRG92TDJObGNuUnpMbUZ3Y0d4bExtTnZiUzkzZDJSeVp6WXVaR1Z5TURFR0NDc0dBUVVGQnpBQmhpVm9kSFJ3T2k4dmIyTnpjQzVoY0hCc1pTNWpiMjB2YjJOemNEQXpMWGQzWkhKbk5qQXlNSUlCSGdZRFZSMGdCSUlCRlRDQ0FSRXdnZ0VOQmdvcWhraUc5Mk5rQlFZQk1JSCtNSUhEQmdnckJnRUZCUWNDQWpDQnRneUJzMUpsYkdsaGJtTmxJRzl1SUhSb2FYTWdZMlZ5ZEdsbWFXTmhkR1VnWW5rZ1lXNTVJSEJoY25SNUlHRnpjM1Z0WlhNZ1lXTmpaWEIwWVc1alpTQnZaaUIwYUdVZ2RHaGxiaUJoY0hCc2FXTmhZbXhsSUhOMFlXNWtZWEprSUhSbGNtMXpJR0Z1WkNCamIyNWthWFJwYjI1eklHOW1JSFZ6WlN3Z1kyVnlkR2xtYVdOaGRHVWdjRzlzYVdONUlHRnVaQ0JqWlhKMGFXWnBZMkYwYVc5dUlIQnlZV04wYVdObElITjBZWFJsYldWdWRITXVNRFlHQ0NzR0FRVUZCd0lCRmlwb2RIUndPaTh2ZDNkM0xtRndjR3hsTG1OdmJTOWpaWEowYVdacFkyRjBaV0YxZEdodmNtbDBlUzh3SFFZRFZSME9CQllFRklGaW9HNHdNTVZBMWt1OXpKbUdOUEFWbjNlcU1BNEdBMVVkRHdFQi93UUVBd0lIZ0RBUUJnb3Foa2lHOTJOa0Jnc0JCQUlGQURBS0JnZ3Foa2pPUFFRREF3TnBBREJtQWpFQStxWG5SRUM3aFhJV1ZMc0x4em5qUnBJelBmN1ZIejlWL0NUbTgrTEpsclFlcG5tY1B2R0xOY1g2WFBubGNnTEFBakVBNUlqTlpLZ2c1cFE3OWtuRjRJYlRYZEt2OHZ1dElETVhEbWpQVlQzZEd2RnRzR1J3WE95d1Iya1pDZFNyZmVvdCIsIk1JSURGakNDQXB5Z0F3SUJBZ0lVSXNHaFJ3cDBjMm52VTRZU3ljYWZQVGp6Yk5jd0NnWUlLb1pJemowRUF3TXdaekViTUJrR0ExVUVBd3dTUVhCd2JHVUdVbTl2ZENCRFFTQXRJRWN6TVNZd0pBWURWUVFMREIxQmNIQnNaU0JEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURVRNQkVHQTFVRUNnd0tRWEJ3YkdVZ1NXNWpMakVMTUFrR0ExVUVCaE1DVlZNd0hoY05NakV3TXpFM01qQXpOekV3V2hjTk16WXdNekU1TURBd01EQXdXakIxTVVRd1FnWURWUVFERER0QmNIQnNaU0JYYjNKc1pIZHBaR1VnUkdWMlpXeHZjR1Z5SUZKbGJHRjBhVzl1Y3lCRFpYSjBhV1pwWTJGMGFXOXVJRUYxZEdodmNtbDBlVEVMTUFrR0ExVUVDd3dDUnpZeEV6QVJCZ05WQkFvTUNrRndjR3hsSUVsdVl5NHhDekFKQmdOVkJBWVRBbFZUTUhZd0VBWUhLb1pJemowQ0FRWUZLNEVFQUNJRFlnQUVic1FLQzk0UHJsV21aWG5YZ3R4emRWSkw4VDBTR1luZ0RSR3BuZ24zTjZQVDhKTUViN0ZEaTRiQm1QaENuWjMvc3E2UEYvY0djS1hXc0w1dk90ZVJoeUo0NXgzQVNQN2NPQithYW85MGZjcHhTdi9FWkZibmlBYk5nWkdoSWhwSW80SDZNSUgzTUJJR0ExVWRFd0VCL3dRSU1BWUJBZjhDQVFBd0h3WURWUjBqQkJnd0ZvQVV1N0Rlb1ZnemlJcWtpcG5ldnIzcnI5ckxKS3N3UmdZSUt3WUJCUVVIQVFFRU9qQTRNRFlHQ0NzR0FRVUZCekFCaGlwb2RIUndPaTh2YjJOemNDNWhjSEJzWlM1amIyMHZiMk56Y0RBekxXRndjR3hsY205dmRHTmhaek13TndZRFZSMGZCREF3TGpBc29DcWdLSVltYUhSMGNEb3ZMMk55YkM1aGNIQnNaUzVqYjIwdllYQndiR1Z5YjI5MFkyRm5NeTVqY213d0hRWURWUjBPQkJZRUZEOHZsQ05SMDFESm1pZzk3YkI4NWMrbGtHS1pNQTRHQTFVZER3RUIvd1FGTUFNQkFmOHdEZ1lEVlIwUEFRSC9CQVFEQWdFR01Bb0dDQ3FHU000OUJBTURBMmdBTUdVQ01RQ0Q2Y0hFRmw0YVhUUVkyZTN2OUd3T0FFWkx1Tit5UmhIRkQvM21lb3locG12T3dnUFVuUFdUeG5TNGF0K3FJeFVDTUcxbWloREsxQTNVVDgyTlF6NjBpbU9sTTI3amJkb1h0MlFmeUZNbStZaGlkRGtMRjF2TFVhZ002QmdENTZLeUtBPT0iXX0.eyJ0cmFuc2FjdGlvbklkIjoiMjAwMDAwMDk3MDYzMTk0OCIsIm9yaWdpbmFsVHJhbnNhY3Rpb25JZCI6IjIwMDAwMDA5NzA0MTcyMzAiLCJ3ZWJPcmRlckxpbmVJdGVtSWQiOiIyMDAwMDAwMTA2NjkyNDI0IiwiYnVuZGxlSWQiOiJjb20udXNraXAuYXBwIiwicHJvZHVjdElkIjoiZmMzNmZmMjBfNDE3Ml8xMWYwXzg1M2JfZmEyMTcyZWNmMWQ4Iiwic3Vic2NyaXB0aW9uR3JvdXBJZGVudGlmaWVyIjoiMjEzOTc0MzYiLCJwdXJjaGFzZURhdGUiOjE3NTM2NTkwMjIwMDAsIm9yaWdpbmFsUHVyY2hhc2VEYXRlIjoxNzUzNTg3Mjc3MDAwLCJleHBpcmVzRGF0ZSI6MTc1MzY1OTMyMjAwMCwicXVhbnRpdHkiOjEsInR5cGUiOiJBdXRvLVJlbmV3YWJsZSBTdWJzY3JpcHRpb24iLCJkZXZpY2VWZXJpZmljYXRpb24iOiJ1RFNpUUFsRERzanJ2akd5MVFYTng1ejFyNFhnM0JCZ3g1YnYrRVZzZkdxT00raktYTEhRMElBZ0VsM1ZpbFM0IiwiZGV2aWNlVmVyaWZpY2F0aW9uTm9uY2UiOiIzMjM5ZTFjNy1kYTg1LTQ0MWQtYjc5OC0wZGJiMTc1MTk5MTciLCJpbkFwcE93bmVyc2hpcFR5cGUiOiJQVVJDSEFTRUQiLCJzaWduZWREYXRlIjoxNzU5MjM3NzIxMTkzLCJlbnZpcm9ubWVudCI6IlNhbmRib3giLCJ0cmFuc2FjdGlvblJlYXNvbiI6IlJFTkVXQUwiLCJzdG9yZWZyb250IjoiQ0lWIiwic3RvcmVmcm9udElkIjoiMTQzNTI3IiwicHJpY2UiOjMzOTAsImN1cnJlbmN5IjoiVVNEIiwiYXBwVHJhbnNhY3Rpb25JZCI6IjcwNDcxMDgzODg3NTYzMzUwMSJ9.zdPQtPTyG0Z2683T5pyGy_NrvNlj5SAySK-Eagcsm3i5W95HqF_zmBUZFRiM3QeJ0-6CbJhIXon12Lixx5d2uQ"
}
```

**Réponse** :
```json
{
  "success": true,
  "subscription": {
    "appleId": "user@example.com",
    "productId": "fc36ff20_4172_11f0_853b_fa2172ecf1d8",
    "expirationDate": "2025-07-27T23:35:22+0000",
    "status": "active"
  }
}
```

#### GET `/user-subscription/{userId}`
Récupère l'état d'abonnement d'un utilisateur.

**Exemple** : `GET /user-subscription/testUser456`

**Réponse** :
```json
{
  "subscription": {
    "appleId": "user@example.com",
    "productId": "fc36ff20_4172_11f0_853b_fa2172ecf1d8",
    "expirationDate": "2025-07-27T23:35:22+0000",
    "status": "active"
  }
}
```

Ou `{"subscription": null}` si aucun abonnement.

## Test

- Lancez `./test_verify.sh` pour tester le POST avec des données d'exemple.
- Vérifiez les logs dans `server.log` pour le debug.

## Fonctionnement interne

- **Décodage JWT** : Le `receiptData` est un JWT signé par Apple. Le serveur le décode manuellement (sans vérification pour les tests) pour extraire le payload (détails de l'abonnement).
- **Stockage** : Les abonnements sont stockés dans un array PHP (`$subscriptions`). Chaque entrée contient `appleId`, `productId`, `expirationDate`, `status`, et `receiptData`.
- **Validation** : À chaque GET, le serveur re-décodage le JWT et vérifie si l'expiration est passée.
- **Gestion d'erreurs** : Retourne des erreurs JSON en cas de données invalides ou manquantes.

## Production

- **Base de données** : Remplacez `$subscriptions` par une DB (ex. PDO pour MySQL).
- **Sécurité** : Implémentez la vérification de signature JWT pour éviter les falsifications.
- **Logging** : Ajoutez un système de logs (ex. Monolog).
- **HTTPS** : Utilisez HTTPS en production.

## Dépannage

- **Erreur "receiptData invalide"** : Vérifiez que le JWT est complet et non tronqué.
- **Serveur ne démarre pas** : Assurez-vous que le port 3001 est libre.
- **Décodage échoue** : Le payload JWT doit contenir `expiresDate` en millisecondes.

Pour plus d'infos, consultez la doc Apple sur les receipts JWT : https://developer.apple.com/documentation/appstoreserverapi/jws_receipt
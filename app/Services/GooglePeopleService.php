<?php

namespace App\Services;

use Google\Client;
use Google\Service\PeopleService;

class GooglePeopleService
{
    protected $client;
    protected $service;

    public function __construct($accessToken)
    {
        $this->client = new Client();
        $this->client->setAccessToken($accessToken);
        $this->service = new PeopleService($this->client);
    }

    /**
     * Fetch all contacts from the authenticated user's Google account.
     *
     * @return array
     */
    public function getContacts()
    {
        $optParams = [
            'personFields' => 'names,emailAddresses,phoneNumbers,photos,biographies',
            'pageSize' => 1000,
        ];

        $results = $this->service->people_connections->listPeopleConnections('people/me', $optParams);
        return $results->getConnections();
    }

    /**
     * Update an existing contact in Google Contacts.
     *
     * @param string $resourceName (e.g., 'people/c123456789')
     * @param string $etag
     * @param array $data ['name' => 'John Doe', 'phone' => '123456789']
     * @return \Google\Service\PeopleService\Person
     */
    public function updateContact($resourceName, $etag, $data)
    {
        $person = new \Google\Service\PeopleService\Person();
        $person->setEtag($etag);

        $updatePersonFields = [];

        if (isset($data['name'])) {
            $name = new \Google\Service\PeopleService\Name();
            $name->setGivenName($data['name']);
            $person->setNames([$name]);
            $updatePersonFields[] = 'names';
        }

        if (isset($data['phone'])) {
            $phone = new \Google\Service\PeopleService\PhoneNumber();
            $phone->setValue($data['phone']);
            $person->setPhoneNumbers([$phone]);
            $updatePersonFields[] = 'phoneNumbers';
        }

        if (array_key_exists('notes', $data)) {
            $bio = new \Google\Service\PeopleService\Biography();
            $bio->setValue($data['notes']);
            $person->setBiographies([$bio]);
            $updatePersonFields[] = 'biographies';
        }

        return $this->service->people->updateContact($resourceName, $person, [
            'updatePersonFields' => implode(',', $updatePersonFields)
        ]);
    }
}

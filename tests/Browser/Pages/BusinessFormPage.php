<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class BusinessFormPage extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/provider/business/edit';
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert( Browser $browser ): void
    {
        $browser->assertPathIs( $this->url() )
            ->assertSee( 'Dados Empresariais' );
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            // Dados Pessoais
            '@first_name'          => '#first_name',
            '@last_name'           => '#last_name',
            '@birth_date'          => '#birth_date',
            '@email_personal'      => '#email_personal',
            '@phone_personal'      => '#phone_personal',

            // Dados Profissionais
            '@company_name'        => '#company_name',
            '@cnpj'                => '#cnpj',
            '@cpf'                 => '#cpf',
            '@area_of_activity_id' => '#area_of_activity_id',
            '@profession_id'       => '#profession_id',
            '@description'         => '#description',

            // Contatos Empresariais
            '@email_business'      => '#email_business',
            '@phone_business'      => '#phone_business',
            '@website'             => '#website',

            // Endereço
            '@cep'                 => '#cep',
            '@address'             => '#address',
            '@address_number'      => '#address_number',
            '@neighborhood'        => '#neighborhood',
            '@city'                => '#city',
            '@state'               => '#state',

            // Botões e outros
            '@submit_button'       => 'button[type="submit"]',
            '@logo_input'          => '#logo',
        ];
    }

    /**
     * Fill personal data section.
     */
    public function fillPersonalData( Browser $browser, array $data ): self
    {
        $browser->type( '@first_name', $data[ 'first_name' ] ?? '' )
            ->type( '@last_name', $data[ 'last_name' ] ?? '' )
            ->type( '@birth_date', $data[ 'birth_date' ] ?? '' )
            ->type( '@email_personal', $data[ 'email_personal' ] ?? '' )
            ->type( '@phone_personal', $data[ 'phone_personal' ] ?? '' );

        return $this;
    }

    /**
     * Fill professional data section.
     */
    public function fillProfessionalData( Browser $browser, array $data ): self
    {
        $browser->type( '@company_name', $data[ 'company_name' ] ?? '' )
            ->type( '@cnpj', $data[ 'cnpj' ] ?? '' )
            ->type( '@cpf', $data[ 'cpf' ] ?? '' )
            ->select( '@area_of_activity_id', $data[ 'area_of_activity_id' ] ?? '' )
            ->select( '@profession_id', $data[ 'profession_id' ] ?? '' )
            ->type( '@description', $data[ 'description' ] ?? '' );

        return $this;
    }

    /**
     * Fill business contact section.
     */
    public function fillBusinessContact( Browser $browser, array $data ): self
    {
        $browser->type( '@email_business', $data[ 'email_business' ] ?? '' )
            ->type( '@phone_business', $data[ 'phone_business' ] ?? '' )
            ->type( '@website', $data[ 'website' ] ?? '' );

        return $this;
    }

    /**
     * Fill address section.
     */
    public function fillAddress( Browser $browser, array $data ): self
    {
        $browser->type( '@cep', $data[ 'cep' ] ?? '' )
            ->type( '@address', $data[ 'address' ] ?? '' )
            ->type( '@address_number', $data[ 'address_number' ] ?? '' )
            ->type( '@neighborhood', $data[ 'neighborhood' ] ?? '' )
            ->type( '@city', $data[ 'city' ] ?? '' )
            ->type( '@state', $data[ 'state' ] ?? '' );

        return $this;
    }

    /**
     * Upload logo file.
     */
    public function uploadLogo( Browser $browser, string $filePath ): self
    {
        $browser->attach( '@logo_input', $filePath );
        return $this;
    }

    /**
     * Submit the form.
     */
    public function submitForm( Browser $browser ): self
    {
        $browser->press( '@submit_button' );
        return $this;
    }

    /**
     * Fill all form sections at once.
     */
    public function fillCompleteForm( Browser $browser, array $allData ): self
    {
        return $this->fillPersonalData( $browser, $allData[ 'personal' ] ?? [] )
            ->fillProfessionalData( $browser, $allData[ 'professional' ] ?? [] )
            ->fillBusinessContact( $browser, $allData[ 'contact' ] ?? [] )
            ->fillAddress( $browser, $allData[ 'address' ] ?? [] );
    }

}

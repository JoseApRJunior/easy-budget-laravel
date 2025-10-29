<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\BusinessFormPage;
use Tests\Browser\Support\TestDataHelper;
use Tests\DuskTestCase;

class FormularioProviderTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Cria arquivo de teste para logo
        TestDataHelper::generateTestLogo();
    }

    /**
     * Clean up after test.
     */
    protected function tearDown(): void
    {
        // Remove arquivos de teste
        TestDataHelper::cleanupTestFiles();

        parent::tearDown();
    }

    /**
     * Test successful form submission with valid data.
     */
    public function test_envio_formulario_com_dados_validos()
    {
        $this->browse( function ( Browser $browser ) {
            $testData = TestDataHelper::validBusinessData();
            $page     = new BusinessFormPage();

            $browser->visit( $page )
                ->on( $page )
                ->fillCompleteForm( $browser, $testData )
                ->uploadLogo( $browser, storage_path( 'app/public/test-logos/test-logo.png' ) )
                ->submitForm( $browser )
                ->waitForLocation( '/settings' )
                ->assertSee( 'Dados atualizados com sucesso' );
        } );
    }

    /**
     * Test form with minimum required fields.
     */
    public function test_formulario_com_campos_minimos()
    {
        $this->browse( function ( Browser $browser ) {
            $testData = TestDataHelper::minimalBusinessData();
            $page     = new BusinessFormPage();

            $browser->visit( $page )
                ->on( $page )
                ->fillCompleteForm( $browser, $testData )
                ->submitForm( $browser )
                ->waitForLocation( '/settings' )
                ->assertSee( 'Dados atualizados com sucesso' );
        } );
    }

    /**
     * Test form validation errors with invalid data.
     */
    public function test_validacao_formulario_com_dados_invalidos()
    {
        $this->browse( function ( Browser $browser ) {
            $testData = TestDataHelper::invalidBusinessData();
            $page     = new BusinessFormPage();

            $browser->visit( $page )
                ->on( $page )
                ->fillCompleteForm( $browser, $testData )
                ->submitForm( $browser )
                ->waitFor( '.is-invalid', 5 )
                ->assertSee( 'O campo nome é obrigatório.' )
                ->assertSee( 'O campo sobrenome é obrigatório.' )
                ->assertSee( 'Data de nascimento deve ser uma data anterior a hoje.' )
                ->assertSee( 'O campo email pessoal deve ser um endereço de email válido.' )
                ->assertSee( 'O campo telefone pessoal deve ser um telefone válido (ex: (11) 99999-9999).' );
        } );
    }

    /**
     * Test form with partial data update.
     */
    public function test_atualizacao_parcial_dados()
    {
        $this->browse( function ( Browser $browser ) {
            $testData = TestDataHelper::partialUpdateData();
            $page     = new BusinessFormPage();

            $browser->visit( $page )
                ->on( $page )
                // Preenche apenas alguns campos
                ->fillPersonalData( $browser, $testData[ 'personal' ] )
                ->fillProfessionalData( $browser, $testData[ 'professional' ] )
                ->fillBusinessContact( $browser, $testData[ 'contact' ] )
                ->submitForm( $browser )
                ->waitForLocation( '/settings' )
                ->assertSee( 'Dados atualizados com sucesso' );
        } );
    }

    /**
     * Test file upload validation.
     */
    public function test_upload_logo_invalido()
    {
        $this->browse( function ( Browser $browser ) {
            $testData = TestDataHelper::validBusinessData();
            $page     = new BusinessFormPage();

            // Cria arquivo de texto inválido
            $invalidFile = storage_path( 'app/public/test-logos/invalid-file.txt' );
            file_put_contents( $invalidFile, 'arquivo de texto inválido' );

            $browser->visit( $page )
                ->on( $page )
                ->fillCompleteForm( $browser, $testData )
                ->attach( '@logo_input', $invalidFile )
                ->submitForm( $browser )
                ->waitFor( '.alert-danger', 5 )
                ->assertSee( 'O arquivo selecionado deve ser uma imagem.' )
                ->assertSee( 'O arquivo selecionado deve ser uma imagem com uma das seguintes extensões: jpg, jpeg, png.' );

            // Remove arquivo inválido
            unlink( $invalidFile );
        } );
    }

    /**
     * Test required fields validation.
     */
    public function test_campos_obrigatorios()
    {
        $this->browse( function ( Browser $browser ) {
            $page = new BusinessFormPage();

            $browser->visit( $page )
                ->on( $page )
                // Tenta submeter sem preencher campos obrigatórios
                ->submitForm( $browser )
                ->waitFor( '.is-invalid', 5 )
                ->assertSee( 'O campo nome é obrigatório.' )
                ->assertSee( 'O campo sobrenome é obrigatório.' )
                ->assertSee( 'O campo nome da empresa é obrigatório.' )
                ->assertSee( 'O campo área de atuação é obrigatório.' )
                ->assertSee( 'O campo profissão é obrigatório.' )
                ->assertSee( 'O campo cep é obrigatório.' )
                ->assertSee( 'O campo logradouro é obrigatório.' )
                ->assertSee( 'O campo bairro é obrigatório.' )
                ->assertSee( 'O campo cidade é obrigatório.' )
                ->assertSee( 'O campo estado é obrigatório.' );
        } );
    }

    /**
     * Test form field interactions and user experience.
     */
    public function test_interacoes_campos_formulario()
    {
        $this->browse( function ( Browser $browser ) {
            $page = new BusinessFormPage();

            $browser->visit( $page )
                ->on( $page )
                // Testa preenchimento e limpeza de campos
                ->type( '@first_name', 'João' )
                ->clear( '@first_name' )
                ->assertValue( '@first_name', '' )

                // Testa seleção em dropdowns
                ->select( '@area_of_activity_id', '1' )
                ->assertSelected( '@area_of_activity_id', '1' )

                // Testa hover em campos
                ->mouseover( '@first_name' )
                ->assertVisible( '@first_name' )

                // Testa foco em campo
                ->click( '@first_name' )
                ->assertFocused( '@first_name' );
        } );
    }

    /**
     * Test responsive behavior on mobile viewport.
     */
    public function test_responsividade_mobile()
    {
        $this->browse( function ( Browser $browser ) {
            $page = new BusinessFormPage();

            $browser->resize( 375, 667 ) // iPhone SE viewport
                ->visit( $page )
                ->on( $page )
                ->assertVisible( '.container-fluid' )
                ->assertVisible( 'h1' )
                ->assertVisible( 'form' )
                // Verifica se formulário é visível e acessível
                ->assertVisible( '@first_name' )
                ->assertVisible( '@submit_button' );
        } );
    }

    /**
     * Test form persistence (data should be preserved on page reload).
     */
    public function test_persistencia_dados_formulario()
    {
        $this->browse( function ( Browser $browser ) {
            $testData = TestDataHelper::validBusinessData();
            $page     = new BusinessFormPage();

            $browser->visit( $page )
                ->on( $page )
                ->fillPersonalData( $browser, $testData[ 'personal' ] )
                // Recarrega página para testar persistência
                ->reload()
                ->waitForPageToLoad()
                // Verifica se dados foram preservados (usando old values)
                ->assertSeeIn( '@first_name', $testData[ 'personal' ][ 'first_name' ] )
                ->assertSeeIn( '@last_name', $testData[ 'personal' ][ 'last_name' ] );
        } );
    }

}

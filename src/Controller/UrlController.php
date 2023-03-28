<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UrlController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }
    
    #[Route('/api/v1/short-urls', name: 'app_url', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {
        //recogemos el token
        $token = $request->headers->get('Authorization');

        //checkeamos el token
        if(self::validarToken($token)){

            //recogemos el valor de url
            $url = $request->get('url');

            if(isset($url) && is_string($url)){

                //obtener URL de API tinyurl
                $respuesta = $this->client->request(
                    'GET',
                    "https://tinyurl.com/api-create.php?url=$url"
                );
        
                $url_short = $respuesta->getContent();

            }else{
                throw new NotFoundHttpException("ERROR: URL no recibida");
            }               

        }else{
            throw new NotFoundHttpException("ERROR: Token Authorization no válido");
        }

        return $this->json([
            'url' => $url_short
        ]);
    }


    public function validarToken($cadena)
    {
        //comprobar si es authorization: bearer
        if(preg_match('/Bearer\s/',$cadena)){

            //obtengo el token a analizar
            $limpioToken = str_replace('Bearer ','',$cadena);       

            //separo la cadena por caracteres como elementos de un array
            $cadenaAnalisis = str_split($limpioToken);

            //patrones de comparacion
            $patronOpen = ['(','[','{'];
            $patronClose = [')',']','}'];    
            $patronCloseAsoc = [')' => '(', ']' => '[', '}' => '{'];

            $caracterEncontrado = [];

            //comparo los caracteres uno a uno
            foreach($cadenaAnalisis as $caracter){

                //si coincide con patronOpen
                if(in_array($caracter, $patronOpen)){
                    //agrego caracter para posterior comprobacion
                    $caracterEncontrado[] = $caracter;
                    //echo "<br>Caracter Open encontrado = ". $caracter;
                                
                //si conidicide con patronClose
                }elseif(in_array($caracter, $patronClose)){
                    
                    //comparo ultimo valor $caracterEncontrado con $patronCloseAsoc
                    if(end($caracterEncontrado)!==$patronCloseAsoc[$caracter]){                        
                        return false;
                        // echo "<br>Valor Close NO coincidente";                    
                    
                    //si coincide, borro ultimo valor $caracterEncontrado
                    }else{
                        array_pop($caracterEncontrado);
                        // echo "<br>Valor Close Coincidente";                                            
                    }

                //si es un caracter no vacío
                }elseif(!empty($caracter)){
                    return false;
                    // echo "<br>No coincide con los caracteres obligatorios para el token";                
                }
            }

            //comprobamos si se han borrado todas las coincidencias de apertura
            if(count($caracterEncontrado)===0){
                return true;
                //echo "Cadena Válida";
            }else{
                return false;
                //echo "Cadena NO válida";
            }        

        }else{
            //no es authorization: bearer
            return false;
        }        
    }
}
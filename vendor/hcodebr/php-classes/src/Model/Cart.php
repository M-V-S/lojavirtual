<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model
{
    const SESSION = "Cart";

    public static function getFromSession()
    {

        $cart = new Cart();
        //se o isset for true e id for > 0, significa que o carrinho já foi inserido no bd e o carrinho esta na sessão
        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
            //carregar o carrinho
            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
           
              
        }else{ //caso o carrinho não exista



            //Recuperar o carrinho aparte do id
            $cart->getFromSessionID();

            //verificar se o carrinho não esta criado
            if (!(int)$cart->getidcart() > 0) {
               //criar id da sessão
               $data = [
                "dessessionid"=>session_id()
               ];
               //[false  não é admin], se retorna true, quer disser que esta logado
               if (User::checkLogin(false)) {
                   //salvar id, se o usuário estiver logado, [carrinho abandonado etc...] 
                   $user = User::getFromSession();

                   $data['iduser'] = $user->getiduser();
               }

               $cart->setData($data);

               $cart->save();

               $cart->setToSession();

              echo "string";
              exit();

               
            }

        }
        return $cart;
    }

    public function setToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

     public function getFromSessionID(){
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid",[
            ":dessessionid"=>session_id()
        ]);


        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    //carregar informações
    public function get(int $idcart){
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart",[
            ":idcart"=>$idcart
        ]);

          if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public function save(){
        $sql = new Sql();

        $results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :zipcode, :vlfreight, :nrdays)",[
            ":idcart"=>$this->getidcart(),
            ":dessessionid"=>$this->getdessessionid(),
            ":iduser"=>$this->getiduser(),
            ":zipcode"=>$this->getzipcode(),
            ":vlfreight"=>$this->getvlfreight(),
            ":nrdays"=>$this->getnrdays()

        ]);

        $this->setData($results[0]);
    }

    public function addProduct(Product $product){
      $sql = new Sql();



     $sql->query("INSERT INTO tb_cartsproducts(idcart, idproduct) VALUES(:idcart, :idproduct)",[
        ":idcart"=>$this->getidcart(),
        "idproduct"=>$product->getidproduct()
      ]);




    }

    public function removeProduct(Product $product, $all = false) {
      $sql = new Sql();

      if ($all) {
        $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
         WHERE idcart = :idcart AND idproduct = :idproduct 
         AND dtremoved is NULL",[
            ":idcart"=>$this->getidcart(),
            ":idproduct"=>$product->getidproduct()
          ]);
      }else{
        $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
         WHERE idcart = :idcart AND idproduct = :idproduct 
         AND dtremoved is NULL LIMIT 1", [
            ":idcart"=>$this->getidcart(),
            ":idproduct"=>$product->getidproduct()
          ]);

      }
    }

    public function getProduct(){
      $sql = new Sql();


          $rows = $sql->select("
            SELECT b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal 
            FROM tb_cartsproducts a 
            INNER JOIN tb_products b ON a.idproduct = b.idproduct 
            WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
            GROUP BY b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl 
            ORDER BY b.desproduct
          ", [
            ':idcart'=>$this->getidcart()
          ]);
       
          return Product::checkList($rows);
    }

 


}


?>
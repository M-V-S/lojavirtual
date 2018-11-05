<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model
{
    const SESSION = "Cart";
    const SESSION_ERROR = "CartError";

    public static function getFromSession()
    {
        $cart = new Cart();
        //se o isset for true e id for > 0, significa que o carrinho já foi inserido no bd e o carrinho esta na sessão
        if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) 
        {
            //carregar o carrinho
            $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);

        } else { //caso o carrinho não exista

            //Recuperar o carrinho aparte do id
            $cart->getFromSessionID();

            //verificar se o carrinho não esta criado
            if (!(int)$cart->getidcart() > 0) 
            {
                //criar id da sessão
                $data = [
                    "dessessionid" => session_id()
                ];
                //[false  não é admin], se retorna true, quer disser que esta logado
                if (User::checkLogin(false)) 
                {
                    //salvar id, se o usuário estiver logado, [carrinho abandonado etc...]
                    $user = User::getFromSession();

                    $data['iduser'] = $user->getiduser();
                }

                $cart->setData($data);

                $cart->save();

                $cart->setToSession();
            }
        }
        return $cart;
    }

    public function setToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    public function getFromSessionID()
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
            ":dessessionid" => session_id()
        ]);

        if (count($results) > 0) 
        {
            $this->setData($results[0]);
        }
    }

    //carregar informações
    public function get(int $idcart)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
            ":idcart" => $idcart
        ]);

        if (count($results) > 0) {
            $this->setData($results[0]);
        }
    }

    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
            ":idcart" => $this->getidcart(),
            ":dessessionid" => $this->getdessessionid(),
            ":iduser" => $this->getiduser(),
            ":deszipcode" => $this->getdeszipcode(),
            ":vlfreight" => $this->getvlfreight(),
            ":nrdays" => $this->getnrdays()

        ]);

        $this->setData($results[0]);
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql();

        $sql->query("INSERT INTO tb_cartsproducts(idcart, idproduct) VALUES(:idcart, :idproduct)", [
            ":idcart" => $this->getidcart(),
            "idproduct" => $product->getidproduct()
        ]);
        //calcular o todos os valores do carrinho
        $this->getCalculateTotal();
    }

    public function removeProduct(Product $product, $all = false)
    {
        $sql = new Sql();
        //se for verdadeiro remover todos
        if ($all) 
        {
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
         WHERE idcart = :idcart AND idproduct = :idproduct 
         AND dtremoved is NULL", [
                ":idcart" => $this->getidcart(),
                ":idproduct" => $product->getidproduct()
            ]);
        } else {
            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
                       WHERE idcart = :idcart AND idproduct = :idproduct 
                       AND dtremoved is NULL LIMIT 1", [
                ":idcart" => $this->getidcart(),
                ":idproduct" => $product->getidproduct()
            ]);
        }

        $this->getCalculateTotal();
    }
    //retornar todos os produtos no carrinho
    public function getProduct()
    {
        $sql = new Sql();

        $rows = $sql->select("
            SELECT b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal 
            FROM tb_cartsproducts a 
            INNER JOIN tb_products b ON a.idproduct = b.idproduct 
            WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
            GROUP BY b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl 
            ORDER BY b.desproduct
          ", [
            ':idcart' => $this->getidcart()
        ]);

        return Product::checkList($rows);
    }

    public function getProductsTotals()
    {
        $sql = new Sql();

        $results = $sql->select("
        SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) 
        AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
        FROM tb_products a
        INNER JOIN tb_cartsproducts b on a.idproduct = b.idproduct
        WHERE b.idcart = :idcart AND dtremoved IS NULL;
        ", [
            ":idcart" => $this->getidcart()
        ]);

        if (count($results[0]) > 0) {
            return $results[0];
        } else {
            return [];
        }
    }

    public function setFreight($nrzipcode)
    {

        $nrzipcode = str_replace('-', ' ', $nrzipcode);

        $totals = $this->getProductsTotals();

        if (count($totals) === 0) {

        } else {
            if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
            if ($totals['vllength'] < 16) $totals['vllength'] = 16;

            $sq = http_build_query([
                'nCdEmpresa' => '',
                'sDsSenha' => '',
                'nCdServico' => '40010',
                'sCepOrigem' => '46440000',
                'sCepDestino' => $nrzipcode,
                'nVlPeso' => $totals['vlweight'],
                'nCdFormato' => '1',
                'nVlComprimento' => $totals['vllength'],
                'nVlAltura' => $totals['vlheight'],
                'nVlLargura' => $totals['vlwidth'],
                'nVlDiametro' => '0',
                'sCdMaoPropria' => 'S',
                'nVlValorDeclarado' => $totals['vlprice'],
                'sCdAvisoRecebimento' => 'S'
            ]);

            $xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?" . $sq);

            $results = $xml->Servicos->cServico;

            if ($results->MsgErro != '') {
                Cart::setMsgErro($results->MsgErro);

            } else {
                $this->clierMsgError($results->MsgErro);
            }

            $this->setnrdays($results->PrazoEntrega);
            //chama a o metodo formatValue.... para tratar pontos etc...
            $this->setvlfreight(Cart::formatValueToDecimal($results->Valor));
            $this->setdeszipcode($nrzipcode);

            $this->save();

            return $results;
        }
    }

    public static function formatValueToDecimal($value): float
    {
        //
        $value = (float)str_replace('.', '', $value);

        return str_replace(',', '.', $value);
    }

    public static function setMsgError($msg)
    {
        $_SESSION[Cart::SESSION_ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : '';

        Cart::clierMsgError();

        return $msg;
    }

    public static function clierMsgError()
    {
        $_SESSION[Cart::SESSION_ERROR] = NULL;
    }

    public function updateFreight()
    {
        if ($this->getdeszipcode() != '') {

            $this->setFreight($this->getdeszipcode());
        }
    }

    public function getValues()
    {
        $this->getCalculateTotal();

        return parent::getValues();
    }

    //calcular preço total dos produtos que tem no carrinho
    public function getCalculateTotal()
    {
        $this->updateFreight();
        $totals = $this->getProductsTotals();

        $this->setvlsubtotal($totals['vlprice']);
        $this->setvltotal($totals['vlprice'] + $this->getvlfreight());
    }
}

<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;


class User extends Model
{

    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";
    const SUCCESS = "UserSucesss";

    public static function getFromSession()
    {
        $user = new User();
        //se o isset for true e id for > 0, significa que o usuário já foi criado no bd e o usuário esta logado
        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
            $user->setData($_SESSION[User::SESSION]);
        }

        return $user;
    }

    //checar login
    public static function checkLogin($inadmin)
    {
        //ver se usuário é um admin
        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]['iduser'] > 0
        ) {

            //não esta logado
            return false;
        } else {

            //só acessa o if se for admin
            if ($inadmin === true && (bool)$_SESSION[User::SESSION]['iduser'] === true) 
            {
                return true;
            } else if ($inadmin === false) {
                return true;
            } else {
                return false;
            }
        }
    }

    public static function login($login, $password)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a inner join tb_persons b on a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));
        //caso entre no if o fluxo é encerrado
        if (count($results) === 0)
        {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }

        $data = $results[0];

        //validar senha
        if (password_verify($password, $data['despassword']) === true)
         {
            $user = new User();

            $data['desperson'] = utf8_encode($data['desperson']);
            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();


            return $user;

        } else {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }


    }

    //verificar se o usuário é da admin ou site
    public static function verifyLogin($inadmin = true)
    {
        if (!User::checkLogin($inadmin)) {
            if ($inadmin) {
                header("Location: /admin/login");
            } else {
                header("Location: /login");
            }
            exit();
        }


    }

    //sair da conta
    public static function logout()
    {
        $_SESSION[User::SESSION] = null;
    }

    //Retorna todos os usuário do banco
    public static function listAll()
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    //salvar usuário no banco
    public function save()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => User::getPasswordHash($this->getdespassword()),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    //retorna dados do usuário de um determinado user
    public function get($iduser)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser" => $iduser
        ));

        $data = $results[0];

        $data['desperson'] = utf8_encode($data['desperson']);

        $this->setData($data);
    }

    //atualizar informações de um determinado usuário
    public function update()
    {
        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser" => $this->getiduser(),
            ":desperson" => $this->getdesperson(),
            ":deslogin" => $this->getdeslogin(),
            ":despassword" => $this->getdespassword(),
            ":desemail" => $this->getdesemail(),
            ":nrphone" => $this->getnrphone(),
            ":inadmin" => $this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    //deletar um user
    public function delete()
    {
        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser" => $this->getiduser()
        ));
    }

    //solicita recuperação de senha
    public static function getForgot($email, $inadmin = true)
    {
        $sql = new Sql();

        //consuta pegar dados da tabela person e user aparte do email
        $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email", array(
            ":email" => $email
        ));

        if (count($results) === 0) {
            throw new \Exception("Não foi possivel recuperar a senha.");
        } else {

            $data = $results[0];

            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser" => $data['iduser'],
                ":desip" => $_SERVER['REMOTE_ADDR']
            ));

            if (count($results2) === 0) {
                throw new \Exception("Não foi possivel recuperar a senha.");
            } else {
                //revisar
                /*$dataRecovery = $results2[0];

                $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));

                if ($inadmin === true) {
                    $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
                } else {
                    $link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
                }*/

                //alteração futura na versão novo do php
                $dataRecovery = $results2[0];
                $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                $code = openssl_encrypt($dataRecovery['idrecovery'], 'aes-256-cbc', User::SECRET, 0, $iv);
                $result = base64_encode($iv . $code);

                //verificar se é admin ou user do site
                if ($inadmin === true) {
                    $link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$result";
                } else {
                    $link = "http://www.hcodecommerce.com.br/forgot/reset?code=$result";
                }

                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha da Hcode Store", "forgot", array(
                    "name" => $data["desperson"],
                    "link" => $link
                ));

                $mailer->send();

                return $data;
            }
        }
    }

    //validar recuperação da senha
    public static function validForgotDecrypt($result)
    {
        //revisar esse trecho
        //implementar esse trexo na nova versão do php
        $result = base64_decode($result);
        $code = mb_substr($result, openssl_cipher_iv_length('aes-256-cbc'), null, '8bit');
        $iv = mb_substr($result, 0, openssl_cipher_iv_length('aes-256-cbc'), '8bit');;
        $idrecovery = openssl_decrypt($code, 'aes-256-cbc', User::SECRET, 0, $iv);


        /*$idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);
        var_dump($idrecovery);
        exit();*/

        $sql = new Sql();

        $results = $sql->select("
				SELECT * 
				FROM tb_userspasswordsrecoveries a
				INNER JOIN tb_users b USING(iduser)
				INNER JOIN tb_persons c USING(idperson)
				WHERE 
				a.idrecovery = :idrecovery
			    AND
			    a.dtrecovery IS NULL
			    AND
			    DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();", array(
            ":idrecovery" => $idrecovery
        ));

        if (count($results) === 0) {
            throw new \Exception("Não foi possivel recuperar a senha.");

        } else {
            return $results[0];
        }
    }

    //atualizar campo da data de recuperação da senha
    public static function setForgotUser($idrecovery)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
            ":idrecovery" => $idrecovery
        ));
    }

    //atualizar senha
    public function setPassword($password)
    {
        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password" => $password,
            ":iduser" => $this->getiduser()
        ));
    }

    public static function setError($msg)
    {
        $_SESSION[User::ERROR] = $msg;
    }

    public static function getError()
    {
        $msg = (isset($_SESSION[User::ERROR])) ? $_SESSION[User::ERROR] : '';

        User::clierError();

        return $msg;
    }

    public static function clierError()
    {
        $_SESSION[User::ERROR] = NULL;
    }

    //codificar senha
    public static function getPasswordHash($password)
    {

        return password_hash($password, PASSWORD_DEFAULT, [
            "const" => 12]);
    }

    /*****Erro campo limpo*******/
    public static function setErrorRegister($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }

    public static function getErrorRegister()
    {
        $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';
        User::clearErrorRegister();

        return $msg;
    }

    public static function clearErrorRegister()
    {
        $_SESSION[User::ERROR_REGISTER] = NULL;
    }

    /*****Verificar se user existe no bd*******/
    public static function checkLoginExist($login)
    {
        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
            ":deslogin" => $login
        ]);

        return (count($results) > 0);
    }

    /*---------Error mensagem -------------------*/
    public static function setSuccess($msg)
    {
        $_SESSION[User::SUCCESS] = $msg;
    }

    public static function getSuccess()
    {
        $msg = (isset($_SESSION[User::SUCCESS])) ? $_SESSION[User::SUCCESS] : '';

        User::clierSuccess();

        return $msg;
    }

    public static function clierSuccess()
    {
        $_SESSION[User::SUCCESS] = NULL;
    }

    public function getOrders()
    {
        $sql = new Sql();

        $results = $sql->select("
          SELECT * FROM  tb_orders a 
          INNER JOIN tb_ordersstatus b USING(idstatus)
          INNER JOIN tb_carts c USING(idcart)
          INNER JOIN tb_users d ON d.iduser = a.iduser
          INNER JOIN tb_addresses e USING(idaddress)
          INNER JOIN tb_persons f ON f.idperson = d.idperson
          WHERE a.iduser = :iduser", [
            ":iduser" => $this->getiduser()
        ]);
        
        return $results;
    }

    public static function getPage($page = 1, $itensPerPage = 10)
    {
        $start = ($page - 1) * $itensPerPage;
        $sql = new Sql();

        $results = $sql->select("
         SELECT SQL_CALC_FOUND_ROWS * 
             FROM tb_users a 
             INNER JOIN tb_persons b USING(idperson) 
             ORDER BY b.desperson
             LIMIT $start, $itensPerPage
        ");

        //select para retorna a quantidade de registros
        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        return [
            "data" => $results,
            "total" => $resultTotal[0]["nrtotal"],
            "pages" => ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
        ];
    }

     public static function getPageSearch($search, $page = 1, $itensPerPage = 10)
    {
        $start = ($page - 1) * $itensPerPage;
        $sql = new Sql();

        $results = $sql->select("
         SELECT SQL_CALC_FOUND_ROWS * 
             FROM tb_users a 
             INNER JOIN tb_persons b USING(idperson)
             WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search 
             ORDER BY b.desperson
             LIMIT $start, $itensPerPage
        ",[
            ':search' => '%'.$search.'%'
        ]);

        //select para retorna a quantidade de registros
        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");

        return [
            "data" => $results,
            "total" => $resultTotal[0]["nrtotal"],
            "pages" => ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
        ];
    }


}


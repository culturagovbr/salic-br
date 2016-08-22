<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GenericModel
 *
 * @author augusto
 */
require_once 'Zend/Db/Table/Abstract.php';
class GenericModel extends Zend_Db_Table_Abstract {

    private $_config;
    protected $_rowClass = "MinC_Db_Table_Row";
    /**
     * @param string $strName
     * @return string
     *
     * @author Ruy Junior Ferreira Silva <ruyjfs@gmail.com>
     * @since 11/08/2016
     *
     * @todo melhorar e amadurecer codigo
     */
    public function getBanco($strName = '')
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        if ($db instanceof Zend_Db_Adapter_Pdo_Mssql) {
            $strName = 'dbo';
        } else {
            $strName = $db->getConfig()['dbname'];
        }
        return  $strName;
    }

    /**
     * @param $strName
     * @param null $strSchema
     *
     * @author Ruy Junior Ferreira Silva <ruyjfs@gmail.com>
     * @since 11/08/2016
     *
     * @todo melhorar e amadurecer codigo
     */
    public static function getSchema($strSchema)
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        if ($db instanceof Zend_Db_Adapter_Pdo_Mssql) {
//            $strSchema = ucfirst($strSchema) . '.dbo';
            if ($strSchema) {
                $strSchema = $strSchema . '.dbo';
            } else {
                if ($this->_schema) {
                    $strSchema = $this->_schema . '.dbo';
                } else {
                    $strSchema = $this->_banco . '.dbo';
                }
            }
        } else {
//            echo '<pre>';
//            var_dump($db);
//            var_dump($strSchema);
//            exit;
        }

        return $strSchema;
    }

    /**
     * @param string $strName
     * @param string $strSchema
     * @return string
     *
     * @author Ruy Junior Ferreira Silva <ruyjfs@gmail.com>
     * @since 11/08/2016
     *
     * @todo melhorar e amadurecer codigo
     */
    public function getName($strName = '', $strSchema = '')
    {
//        $db = Zend_Db_Table::getDefaultAdapter();

//        if ($strSchema === '') $strSchema = $this->_schema;
//        if ($strName === '') $strName = $this->_name;

//        if ($db->getConfig()['host'] != '10.1.20.44') {
//            $strName = ucfirst($strSchema) . '.dbo.' . $strName;
//            $strName = ucfirst($strName);
//        } else {
//            $strName = strtolower($strSchema) . '.' . $strName;
//            $strName = strtolower($strName);
//        }

        $strName = strtolower($strName);

        return $strName;
    }

    public function init()
    {
        # Tratando o nome da tabela conforme o tipo de banco.
        $this->_name = self::getName($this->_name);
        $this->_banco = self::getBanco($this->_banco);
        $this->_schema = self::getSchema($this->_schema);
    }

    /**
     * GenericModel constructor.
     *
     * @todo verificar um tipo de SET TEXTSIZE 2147483647 para usar com o Postgres tambem.
     */
    public function __construct() {

        # FECHANDO A CONEXAO EXISTENTE JA QUE UMA NOVA SERA ABERTA
        $db = Zend_Db_Table::getDefaultAdapter();
        if(!empty($db)){
            $db->closeConnection();
            unset ($db);
        }

        if (!($this->_config instanceof  Zend_Config_Ini)) {
            $this->_config = new Zend_Config_Ini(
                Zend_Registry::get('DIR_CONFIG'),
                'conexao_' . strtolower($this->_banco),
                array('allowModifications' => true,)
            );
            Zend_Registry::getInstance()->set('config', $this->_config);
            Zend_Db_Table::setDefaultAdapter(Zend_Db::factory($this->_config->db));
            parent::__construct();

            # Setar o campo texto maior que 4096 caracteres aceitaveis por padrao no PHP
//            $this->_db->query('SET TEXTSIZE 2147483647');
        }
    }

    public function  __destruct() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->closeConnection();
    }

    /**
     * Retorna registros do banco de dados
     * @param array $where - array com dados where no formato "nome_coluna_1"=>"valor_1","nome_coluna_2"=>"valor_2"
     * @param array $order - array com orders no formado "coluna_1 desc","coluna_2"...
     * @param int $tamanho - numero de registros que deve retornar
     * @param int $inicio - offset
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function buscar($where = array(), $order = array(), $tamanho = -1, $inicio = -1) {
        $slct = $this->select();

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        //adicionando linha order ao select
        $slct->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }
        return $this->fetchAll($slct);
    }

    public function alterar($dados, $where, $dbg=false) {
        if ($dbg) {
            x($this->dbg($dados, $where));
        }
        $update = $this->update($dados, $where);
        return $update;
    }

    public function apagar($where) {
        $delete = $this->delete($where);
        return $delete;
    }

    public function inserir($dados, $dbg = null) {
        if ($dbg) {
            xd($this->dbg($dados));
        }
        $insert = $this->insert($dados);
        return $insert;
    }

    public function dbg($dados, $where=null) {
        if (!$where) {
            $sql = "INSERT INTO " . $this->_name . " (";
            $keys = array_keys($dados);
            $sql.= implode(',', $keys);
            $sql .= ")\n values ('";
            $values = array_values($dados);
            $sql .= implode("','", $values);
            $sql .= "');";
        } else {
            $sql = "UPDATE " . $this->_name . " SET ";
            foreach ($dados as $coluna => $valor) {
                $sql .= $coluna . " = '" . $valor . "', \n";
            }
            $sql .= "\n" . $where;
        }
        xd($sql);
    }

    protected function _getCols()
    {
        if (null === $this->_cols) {
            $this->_setupMetadata();
            $this->_cols = array_keys($this->_metadata);
            foreach($this->_cols as $indice => $coluna)
            {
                $this->_cols[$indice] = strtolower($coluna);
            }
        }

        return $this->_cols;
    }

    /**
     * Initialize primary key from metadata.
     * If $_primary is not defined, discover primary keys
     * from the information returned by describeTable().
     *
     * @return void
     * @throws Zend_Db_Table_Exception
     */
    protected function _setupPrimaryKey()
    {
        if (!$this->_primary) {
            $this->_setupMetadata();
            $this->_primary = array();
            foreach ($this->_metadata as $col) {
                if ($col['PRIMARY']) {
                    $this->_primary[ $col['PRIMARY_POSITION'] ] = $col['COLUMN_NAME'];
                    if ($col['IDENTITY']) {
                        $this->_identity = $col['PRIMARY_POSITION'];
                    }
                }
            }
            // if no primary key was specified and none was found in the metadata
            // then throw an exception.
            if (empty($this->_primary)) {
                require_once 'Zend/Db/Table/Exception.php';
                throw new Zend_Db_Table_Exception('A table must have a primary key, but none was found');
            }
        } else if (!is_array($this->_primary)) {
            $this->_primary = array(1 => $this->_primary);
        } else if (isset($this->_primary[0])) {
            array_unshift($this->_primary, null);
            unset($this->_primary[0]);
        }
        $this->_primary[1] = strtolower($this->_primary[1]);

        $cols = $this->_getCols();
        if (! array_intersect((array) $this->_primary, $cols) == (array) $this->_primary) {
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception("Primary key column(s) ("
                . implode(',', (array) $this->_primary)
                . ") are not columns in this table ("
                . implode(',', $cols)
                . ")");
        }

        $primary    = (array) $this->_primary;
        $pkIdentity = $primary[(int) $this->_identity];

        /**
         * Special case for PostgreSQL: a SERIAL key implicitly uses a sequence
         * object whose name is "<table>_<column>_seq".
         */
        if ($this->_sequence === true && $this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $this->_sequence = $this->_db->quoteIdentifier("{$this->_name}_{$pkIdentity}_seq");
            if ($this->_schema) {
                $this->_sequence = $this->_db->quoteIdentifier($this->_schema) . '.' . $this->_sequence;
            }
        }
    }

}

?>

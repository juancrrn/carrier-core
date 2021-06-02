<?php

namespace Carrier\Domain;

/**
 * Repository interface (repository pattern)
 * 
 * @package carrier-core
 *
 * @author carrier
 *
 * @version 0.0.1
 */

interface Repository
{

    /**
     * Constructor.
     * 
     * @param \mysqli $db   Conexión a la base de datos.
     */
    public function __construct(\mysqli $db);

    /**
     * Inserta el objeto en la base de datos.
     * 
     * @param static $this  Objeto a insertar.
     * 
     * @return bool|int El identificador del objeto insertado si no ha habido
     *                  ningún problema, o false en caso contrario.
     */
    //public function insert(): bool|int;

    /**
     * Actualiza o inserta el objeto en la base de datos, según si tiene o no
     * valor en la propiedad $id.
     * 
     * @param static $this  Objeto a actualizar.
     * 
     * @return bool True si se ha actualizado correctamente o false si ha habido
     *              algún problema.
     */
    //public function update(): bool;

    /**
     * Comprueba si existe un objeto en la base de datos en base a su id.
     * 
     * @param int $id       Identificador del objeto.
     * 
     * @return bool|int     False si no existe o el id en caso contrario.
     */
    public function findById(int $id): bool|int;

    /**
     * Recoge un objeto de la base de datos.
     * 
     * @requires        Existe un usuario con el identificador especificado.
     * 
     * @param int $id   Identificador del objeto.
     * 
     * @return mixed    Objeto recogido.
     */
    public function retrieveById(int $id): mixed;

    /**
     * Recoge todos los objetos de la base de datos.
     * 
     * @return array        Lista con todos los objetos.
     */
    public function retrieveAll(): array;

    /**
     * Comprueba si un objeto se puede eliminar, es decir, que no está 
     * referenciado como clave ajena en otra tabla.
     * 
     * @requires            El objeto existe.
     * 
     * @param int $id       Identificador del objeto.
     * 
     * @return array        En caso de haberlas, devuelve un array con los
     *                      nombres de las tablas donde hay referencias al
     *                      objeto. Si no las hay, devuelve false.
     */
    public function verifyConstraintsById(int $id): bool|array;

    /**
     * Elimina un objeto de la base de datos.
     * 
     * @requires            El objeto existe.
     * 
     * @param int $id       Identificador del objeto.
     * 
     * @return bool         Resultado de la ejecución de la sentencia.
     */
    public function deleteById(int $id): void;
}
/**
 * @typedef {Object} PhpTestActivityEntity
 * @property {int} id
 * @property {int} php_test_id
 * @property {float} elapsed_seconds
 * @property {string} created = 'CURRENT_TIMESTAMP'
 * @property {PhpTestsEntity} PhpTests php_test_id => id
 * @property {Array} content
 */

/**
 * @typedef {Object} PhpTestsEntity
 * @property {int} id
 * @property {string} repository
 * @property {string} ref
 * @property {string} sha
 * @property {string} status = 'new'
 * @property {string} created = 'CURRENT_TIMESTAMP'
 * @property {string} updated = 'CURRENT_TIMESTAMP'
 * @property {PhpTestActivityEntity[]} PhpTestActivity php_test_id => id
 */


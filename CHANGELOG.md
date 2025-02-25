<a name="unreleased"></a>
## [Unreleased]


<a name="v0.0.6"></a>
## [v0.0.6](https://github.com/biblioverse/TypesenseBundle/compare/v0.0.5...v0.0.6)

### Feat

* Remove support for php 8.1

### Fix

* upstream Typesense-php issue with PSR-18 client

### BREAKING CHANGE


ClientSingletonFactory->getClient is now private

Remove php 8.1 support


<a name="v0.0.5"></a>
## [v0.0.5](https://github.com/biblioverse/TypesenseBundle/compare/v0.0.4...v0.0.5)

### Fix

* **deps:** update dependency typesense/typesense-php to v5


<a name="v0.0.4"></a>
## [v0.0.4](https://github.com/biblioverse/TypesenseBundle/compare/v0.0.3...v0.0.4)

### Feat

* **mapping:** Add field_converter option to use an external service
* **populate:** allow auto embedding fields

### BREAKING CHANGE


use ValueConversionException::fromType instead of
default constructor

EntityTransformer needs an extra argument

isMapped added to FieldMappingInterface, Changes on VectorQueryInterface


<a name="v0.0.3"></a>
## [v0.0.3](https://github.com/biblioverse/TypesenseBundle/compare/v0.0.2...v0.0.3)

### Feat

* **populate:** Wait for readiness before populating

### Fix

* **ci:** Docker build on MR only
* **populate:** Do not alias on failure


<a name="v0.0.2"></a>
## [v0.0.2](https://github.com/biblioverse/TypesenseBundle/compare/v0.0.1...v0.0.2)

### Feat

* **ci:** Build docker image
* **ci:** Force conventional commits
* **ci:** move changelog
* **converter:** Support for enums
* **coverage:** Coverage comment based on previous coverage

### Fix

* **ci:** spacing issues


<a name="v0.0.1"></a>
## v0.0.1

### Feat

* **doc:** Generate via cicd using daux

### Fix

* Avoid Service not found in MapperLocator


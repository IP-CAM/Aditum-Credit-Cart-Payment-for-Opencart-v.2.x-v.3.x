## Módulo integração Aditum para Opencart

## Compatibilidade

 OpenCart 1.5.x à 2.3.x

## Funcionalidades

 Integrar sua loja virtual OpenCart com o gateway de pagamentos [Aditum](http://aditum.com.br)

 Transações de cartão de crédito

 Transações de boleto bancário

 Atualização de status automática
 
 Configuração de parcelamento de cartão


## Instalação

### **PASSO 1**

Acesse o menu **Extensões > Instalador**

ou 

**Extensions > Installer**

O Arquivo aditum-opencart.ocmod.zip e aguarde a instalação.

### **PASSO 2**

Depois acesse **Extensões > Modificações**

ou 

**Extensions > Modifications**

E limpe o cache de modificações clicando no botão AZUL do lado superior esquerdo.

Veja se Aditum vai aparecer na lista de modificações.

### **PRONTO! TUDO INSTALADO :)**

Essas modificações são para adicionar o botão para baixar boleto na página de obrigado. Assim como o código de barras.

## Campos Customizáveis

Para as versões 2.x do Opencart é necessário criar alguns campos para que o módulo funcione perfeitamente, o campo obrigatório é de CPF/CNPJ e os opcionais são de número da casa e complemento.
Você pode escolher em ter apenas o campo de CPF ou apenas o campo de CNPJ.

Acesse seu painel administrador, selecione a opção `Customer > Custom Fields`, e clique para adicionar um novo campo customizável.

![Custom Fields](https://i.imgur.com/Enz7Vdf.png)

Os campos que podem ser criados para que a integração entenda são `CPF`, `CNPJ`, `Número` e `Complemento`.

Crie o campo de CPF caso venda para pessoas físicas e o de CNPJ caso venda para pessoas jurídicas.

### CPF

| *Campo do OpenCart* | *Valor Recomendado*                         | *Obrigatoriedade* |
|---------------------|---------------------------------------------|-------------------|
| Custom Field Name   | CPF                                         | Obrigatório       |
| Location            | Account                                     | Obrigatório       |
| Type                | Text                                        | Obrigatório       |
| Customer Group      | Habilite os grupos que possuirão esse campo | Obrigatório       |
| Required            | Habilitado                                  | Obrigatório       |
| Status              | Habilitado                                  | Obrigatório       |
| Sort Order          | 3                                           | Opcional          |


### CNPJ

| *Campo do OpenCart* | *Valor Recomendado*                         | *Obrigatoriedade* |
|---------------------|---------------------------------------------|-------------------|
| Custom Field Name   | CNPJ                                        | Obrigatório       |
| Location            | Account                                     | Obrigatório       |
| Type                | Text                                        | Obrigatório       |
| Customer Group      | Habilite os grupos que possuirão esse campo | Obrigatório       |
| Required            | Habilitado                                  | Obrigatório       |
| Status              | Habilitado                                  | Obrigatório       |
| Sort Order          | 3                                           | Opcional          |

### Número 

| *Campo do OpenCart* | *Valor Recomendado*                         | *Obrigatoriedade* |
|---------------------|---------------------------------------------|-------------------|
| Custom Field Name   | Número                                      | Obrigatório       |
| Location            | Address                                     | Obrigatório       |
| Type                | Text                                        | Obrigatório       |
| Customer Group      | Habilite os grupos que possuirão esse campo | Obrigatório       |
| Required            | Opcional                                    | Opcional          |
| Status              | Opcional                                    | Opcional          |
| Sort Order          | 2                                           | Opcional          |

### Complemento

| *Campo do OpenCart* | *Valor Recomendado*                         | *Obrigatoriedade* |
|---------------------|---------------------------------------------|-------------------|
| Custom Field Name   | Complemento                                 | Obrigatório       |
| Location            | Address                                     | Obrigatório       |
| Type                | Text                                        | Obrigatório       |
| Customer Group      | Habilite os grupos que possuirão esse campo | Obrigatório       |
| Required            | Opcional                                    | Opcional          |
| Status              | Opcional                                    | Opcional          |
| Sort Order          | 3                                           | Opcional          |



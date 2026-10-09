"""Pydantic validators for Câmara API responses.

Per-field validation (Decision 22): required fields are validated,
optional fields are allowed to be missing. Invalid records go to quarantine.
"""

from datetime import date

from pydantic import BaseModel, field_validator


class DeputadoModel(BaseModel):
    """Validator for Câmara API deputado response."""

    id: int
    nome: str
    siglaPartido: str
    siglaUf: str
    cpf: str | None = None
    email: str | None = None
    urlFoto: str | None = None
    numero: int | None = None

    @field_validator("siglaUf")
    @classmethod
    def validate_uf(cls, v: str) -> str:
        if len(v) != 2:
            raise ValueError(f"UF must be 2 chars, got: {v}")
        return v.upper()

    @field_validator("nome")
    @classmethod
    def validate_nome(cls, v: str) -> str:
        if not v.strip():
            raise ValueError("nome cannot be empty")
        return v.strip()


class MandatoModel(BaseModel):
    """Validator for Câmara API mandato response."""

    dataInicio: str
    siglaUf: str
    dataFim: str | None = None
    isSuplente: bool = False

    @field_validator("siglaUf")
    @classmethod
    def validate_uf(cls, v: str) -> str:
        if len(v) != 2:
            raise ValueError(f"UF must be 2 chars, got: {v}")
        return v.upper()


class ProposicaModel(BaseModel):
    """Validator for Câmara API proposicao response."""

    siglaTipo: str
    numero: int
    ano: int
    ementa: str
    dataApresentacao: str
    idDeputadoAutor: int | None = None
    status: str | None = None
    resumo: str | None = None
    urlInteiroTeor: str | None = None

    @field_validator("siglaTipo")
    @classmethod
    def validate_sigla_tipo(cls, v: str) -> str:
        if not v.strip():
            raise ValueError("siglaTipo cannot be empty")
        return v.strip().upper()

    @field_validator("ementa")
    @classmethod
    def validate_ementa(cls, v: str) -> str:
        if not v.strip():
            raise ValueError("ementa cannot be empty")
        return v.strip()


class VotoModel(BaseModel):
    """Validator for Câmara API voto response."""

    dataSessao: str
    voto: str
    idDeputado: int | None = None
    numeroSessao: str | None = None

    @field_validator("voto")
    @classmethod
    def validate_voto(cls, v: str) -> str:
        if not v.strip():
            raise ValueError("voto cannot be empty")
        return v.strip()


class DespesaModel(BaseModel):
    """Validator for Câmara API despesa response."""

    tipoDespesa: str
    valorDocumento: float | int | str
    dataDocumento: str
    idDeputado: int | None = None
    descricao: str | None = None
    nomeFornecedor: str | None = None
    numeroDocumento: str | None = None
    urlDocumento: str | None = None

    @field_validator("tipoDespesa")
    @classmethod
    def validate_tipo_despesa(cls, v: str) -> str:
        if not v.strip():
            raise ValueError("tipoDespesa cannot be empty")
        return v.strip()

    @field_validator("valorDocumento")
    @classmethod
    def validate_valor(cls, v):
        if v is None:
            raise ValueError("valorDocumento cannot be None")
        return v

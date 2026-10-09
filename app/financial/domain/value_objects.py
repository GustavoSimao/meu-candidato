"""Financial domain value objects."""

from dataclasses import dataclass
from enum import Enum


class ExpenseType(str, Enum):
    """Common expense types for parliamentary quota."""

    COMBUSTIVEL = "combustivel"
    MANUTENCAO = "manutencao"
    TELEFONIA = "telefonia"
    MATERIAL_ESCRITORIO = "material_escritorio"
    PASSAGENS_AEREAS = "passagens_aereas"
    LOCACAO_VEICULOS = "locacao_veiculos"
    SERVICOS_TERCEIROS = "servicos_terceiros"
    ALIMENTACAO = "alimentacao"
    HOSPEDAGEM = "hospedagem"
    OUTROS = "outros"

    @classmethod
    def all_values(cls) -> list[str]:
        return [v.value for v in cls]


class DonorType(str, Enum):
    """Donor types for campaign finance."""

    PESSOA_FISICA = "pessoa_fisica"
    PESSOA_JURIDICA = "pessoa_juridica"
    FUNDO_PARTIDARIO = "fundo_partidario"
    RECURSOS_PROPRIOS = "recursos_proprios"

    @classmethod
    def all_values(cls) -> list[str]:
        return [v.value for v in cls]


class ElectionType(str, Enum):
    """Election types."""

    FEDERAL = "federal"
    ESTADUAL = "estadual"
    MUNICIPAL = "municipal"

    @classmethod
    def all_values(cls) -> list[str]:
        return [v.value for v in cls]


@dataclass(frozen=True)
class Amount:
    """Monetary amount in centavos (avoids floating point issues)."""

    centavos: int

    def __post_init__(self):
        if self.centavos < 0:
            raise ValueError("Amount cannot be negative")

    @classmethod
    def from_reais(cls, reais: float) -> "Amount":
        return cls(int(round(reais * 100)))

    @property
    def reais(self) -> float:
        return self.centavos / 100

    def __add__(self, other: "Amount") -> "Amount":
        return Amount(self.centavos + other.centavos)

    def __sub__(self, other: "Amount") -> "Amount":
        return Amount(self.centavos - other.centavos)

    def __str__(self) -> str:
        return f"R$ {self.reais:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")
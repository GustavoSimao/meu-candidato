"""Legislative Activity domain value objects."""

from dataclasses import dataclass
from enum import Enum


class VoteValue(str, Enum):
    """Valid vote values."""

    FAVOR = "favor"
    CONTRA = "contra"
    AUSENTE = "ausente"
    ABSTENCAO = "abstencao"
    OBSTRUCAO = "obstrucao"
    ART17 = "art17"
    DESCONHECIDO = "desconhecido"

    @classmethod
    def all_values(cls) -> list[str]:
        return [v.value for v in cls]

    def is_favorable(self) -> bool:
        return self == VoteValue.FAVOR

    def is_against(self) -> bool:
        return self == VoteValue.CONTRA

    def is_absent(self) -> bool:
        return self == VoteValue.AUSENTE

    def is_abstention(self) -> bool:
        return self == VoteValue.ABSTENCAO

    def is_obstruction(self) -> bool:
        return self == VoteValue.OBSTRUCAO

    def is_art17(self) -> bool:
        return self == VoteValue.ART17

    def is_unknown(self) -> bool:
        return self == VoteValue.DESCONHECIDO


class PropositionType(str, Enum):
    """Common proposition types."""

    PL = "PL"  # Projeto de Lei
    PDC = "PDC"  # Projeto de Decreto Legislativo
    REQ = "REQ"  # Requerimento
    PEC = "PEC"  # Proposta de Emenda à Constituição
    MPV = "MPV"  # Medida Provisória
    PLP = "PLP"  # Projeto de Lei Complementar
    PRE = "PRE"  # Projeto de Resolução
    RIC = "RIC"  # Regimento Interno

    @classmethod
    def all_values(cls) -> list[str]:
        return [v.value for v in cls]


class LegislativeHouse(str, Enum):
    """Legislative houses."""

    CAMARA = "camara"
    SENADO = "senado"

    @classmethod
    def all_values(cls) -> list[str]:
        return [v.value for v in cls]


@dataclass(frozen=True)
class ExternalId:
    """External ID value object for propositions."""

    value: str

    def __post_init__(self):
        if not self.value or not self.value.strip():
            raise ValueError("External ID cannot be empty")
        object.__setattr__(self, "value", self.value.strip())

    def __str__(self) -> str:
        return self.value
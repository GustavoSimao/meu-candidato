"""Tests for engagement domain entities and value objects."""

from datetime import date, datetime
import pytest

from app.engagement.domain.entities import Badge, BadgeRule, BadgeType, Follow
from app.engagement.domain.value_objects import UserId, PoliticianId


class TestFollowEntity:
    def test_create_follow(self):
        follow = Follow(user_id="user123", politician_id=1)
        assert follow.user_id == "user123"
        assert follow.politician_id == 1

    def test_is_following(self):
        follow = Follow(user_id="user123", politician_id=1)
        assert follow.is_following(1) is True
        assert follow.is_following(2) is False


class TestBadgeEntity:
    def test_create_badge(self):
        badge = Badge(politician_id=1, badge_type=BadgeType.FICHA_LIMPA)
        assert badge.politician_id == 1
        assert badge.badge_type == BadgeType.FICHA_LIMPA

    def test_badge_type_enum(self):
        badge = Badge(politician_id=1, badge_type=BadgeType.PRESENCA_ALTA)
        assert badge.badge_type == BadgeType.PRESENCA_ALTA


class TestBadgeRuleEntity:
    def test_create_badge_rule(self):
        rule = BadgeRule(
            badge_type=BadgeType.LEGISLADOR_ATIVO,
            name="Legislador Ativo",
            description="Apresentou mais de 10 proposições no ano",
            condition="proposicoes_count >= 10",
            threshold=10,
        )
        assert rule.badge_type == BadgeType.LEGISLADOR_ATIVO
        assert rule.threshold == 10

    def test_check_condition_pass(self):
        rule = BadgeRule(
            badge_type=BadgeType.LEGISLADOR_ATIVO,
            name="Legislador Ativo",
            description="Apresentou mais de 10 proposições no ano",
            condition="proposicoes_count >= 10",
            threshold=10,
        )
        assert rule.check_condition(15) is True

    def test_check_condition_fail(self):
        rule = BadgeRule(
            badge_type=BadgeType.LEGISLADOR_ATIVO,
            name="Legislador Ativo",
            description="Apresentou mais de 10 proposições no ano",
            condition="proposicoes_count >= 10",
            threshold=10,
        )
        assert rule.check_condition(5) is False


class TestBadgeTypeEnum:
    def test_all_values(self):
        values = BadgeType.all_values()
        assert "ficha_limpa" in values
        assert "presenca_alta" in values
        assert "legislador_ativo" in values


class TestUserIdValueObject:
    def test_valid_user_id(self):
        uid = UserId("user123")
        assert str(uid) == "user123"

    def test_empty_user_id_raises(self):
        with pytest.raises(ValueError):
            UserId("")


class TestPoliticianIdValueObject:
    def test_valid_politician_id(self):
        pid = PoliticianId(1)
        assert int(pid) == 1

    def test_invalid_politician_id_raises(self):
        with pytest.raises(ValueError):
            PoliticianId(0)

    def test_negative_politician_id_raises(self):
        with pytest.raises(ValueError):
            PoliticianId(-1)
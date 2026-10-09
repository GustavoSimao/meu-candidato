"""Engagement API router."""

from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, status

from app.engagement.api.dependencies import (
    badge_filter,
    badge_rule_filter,
    badge_rule_service,
    badge_service,
    follow_filter,
    follow_service,
    proposition_service,
    vote_service,
)
from app.engagement.application.dtos import (
    BadgeDTO,
    BadgeListDTO,
    BadgeRuleCreateDTO,
    BadgeRuleDTO,
    BadgeRuleListDTO,
    BadgeRuleUpdateDTO,
    FollowCreateDTO,
    FollowDTO,
    FollowListDTO,
    DashboardDTO,
)
from app.engagement.application.filters import BadgeFilterDTO, BadgeRuleFilterDTO, FollowFilterDTO
from app.engagement.application.services import BadgeRuleService, BadgeService, FollowService
from app.engagement.domain.exceptions import (
    AlreadyFollowingError,
    BadgeNotFoundError,
    BadgeRuleNotFoundError,
    FollowNotFoundError,
    InvalidBadgeTypeError,
)
from app.legislative_activity.application.services import PropositionService, VoteService

router = APIRouter(prefix="/engagement", tags=["engagement"])


# Follow endpoints
@router.post("/follows", response_model=FollowDTO, status_code=status.HTTP_201_CREATED)
async def follow_politician(
    data: FollowCreateDTO,
    service: Annotated[FollowService, Depends(follow_service)],
) -> FollowDTO:
    try:
        return await service.follow(data.user_id, data.politician_id)
    except AlreadyFollowingError as e:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(e))


@router.get("/follows", response_model=FollowListDTO)
async def list_follows(
    filters: Annotated[FollowFilterDTO, Depends(follow_filter)],
    service: Annotated[FollowService, Depends(follow_service)],
) -> FollowListDTO:
    return await service.list_follows(filters)


@router.delete("/follows/{politician_id}", status_code=status.HTTP_204_NO_CONTENT)
async def unfollow_politician(
    politician_id: int,
    user_id: Annotated[str, Query()],
    service: Annotated[FollowService, Depends(follow_service)],
) -> None:
    try:
        await service.unfollow(user_id, politician_id)
    except FollowNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Follow not found")


@router.get("/dashboard", response_model=DashboardDTO)
async def get_dashboard(
    user_id: Annotated[str, Query()],
    follow_svc: Annotated[FollowService, Depends(follow_service)],
    badge_svc: Annotated[BadgeService, Depends(badge_service)],
) -> DashboardDTO:
    followed = await follow_svc.get_user_dashboard(user_id)
    badges = []
    for politician_id in followed:
        pol_badges = await badge_svc.get_politician_badges(politician_id)
        badges.extend(pol_badges)

    return DashboardDTO(
        followed_politicians=followed,
        badges=badges,
        recent_activity=[],  # Would need integration with legislative activity
    )


# Badge endpoints
@router.post("/badges", response_model=BadgeDTO, status_code=status.HTTP_201_CREATED)
async def award_badge(
    politician_id: Annotated[int, Query()],
    badge_type: Annotated[str, Query()],
    service: Annotated[BadgeService, Depends(badge_service)],
) -> BadgeDTO:
    try:
        return await service.award_badge(politician_id, badge_type)
    except InvalidBadgeTypeError as e:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail=str(e))


@router.get("/badges", response_model=BadgeListDTO)
async def list_badges(
    filters: Annotated[BadgeFilterDTO, Depends(badge_filter)],
    service: Annotated[BadgeService, Depends(badge_service)],
) -> BadgeListDTO:
    return await service.list_badges(filters)


@router.get("/badges/politician/{politician_id}", response_model=list[BadgeDTO])
async def get_politician_badges(
    politician_id: int,
    service: Annotated[BadgeService, Depends(badge_service)],
) -> list[BadgeDTO]:
    return await service.get_politician_badges(politician_id)


@router.post("/badges/check", response_model=list[BadgeDTO])
async def check_and_award_badges(
    politician_id: Annotated[int, Query()],
    service: Annotated[BadgeService, Depends(badge_service)],
    prop_service: Annotated[PropositionService, Depends(proposition_service)],
    vote_svc: Annotated[VoteService, Depends(vote_service)],
) -> list[BadgeDTO]:
    # Get real metrics from legislative activity
    props = await prop_service.list(prop_filter=None)  # We'll need to create a filter
    votes = await vote_svc.list(vote_filter=None)  # We'll need to create a filter

    # For now, filter by politician_id manually since we don't have filter deps here
    # In a real implementation, we'd have proper filter dependencies
    from app.legislative_activity.application.filters import PropositionFilterDTO, VoteFilterDTO

    prop_filters = PropositionFilterDTO(politician_id=politician_id, page=1, per_page=1000)
    vote_filters = VoteFilterDTO(politician_id=politician_id, page=1, per_page=1000)

    prop_list = await prop_service.list(prop_filters)
    vote_list = await vote_svc.list(vote_filters)

    # Count proposicoes this year
    from datetime import date
    current_year = date.today().year
    proposicoes_count = sum(1 for p in prop_list.items if p.presentation_date and p.presentation_date.year == current_year)

    # Count votes this year
    votes_count = sum(1 for v in vote_list.items if v.session_date and v.session_date.year == current_year)

    # Count favorable votes
    favor_count = sum(1 for v in vote_list.items if v.vote_value == "favor")

    metrics = {
        "ficha_limpa": 1,  # Would need criminal records check
        "presenca_alta": favor_count + votes_count // 2,  # Simplified presence metric
        "legislador_ativo": proposicoes_count,
    }
    return await service.check_and_award_badges(politician_id, metrics)


# Badge Rule endpoints
@router.post("/badge-rules", response_model=BadgeRuleDTO, status_code=status.HTTP_201_CREATED)
async def create_badge_rule(
    data: BadgeRuleCreateDTO,
    service: Annotated[BadgeRuleService, Depends(badge_rule_service)],
) -> BadgeRuleDTO:
    try:
        return await service.create_rule(data)
    except InvalidBadgeTypeError as e:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail=str(e))
    except Exception as e:
        if "CONFLICT" in str(e):
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail=str(e))
        raise


@router.get("/badge-rules", response_model=BadgeRuleListDTO)
async def list_badge_rules(
    filters: Annotated[BadgeRuleFilterDTO, Depends(badge_rule_filter)],
    service: Annotated[BadgeRuleService, Depends(badge_rule_service)],
) -> BadgeRuleListDTO:
    return await service.list_rules(filters)


@router.get("/badge-rules/{badge_type}", response_model=BadgeRuleDTO)
async def get_badge_rule(
    badge_type: str,
    service: Annotated[BadgeRuleService, Depends(badge_rule_service)],
) -> BadgeRuleDTO:
    try:
        return await service.get_rule_by_type(badge_type)
    except BadgeRuleNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Badge rule not found")


@router.patch("/badge-rules/{badge_type}", response_model=BadgeRuleDTO)
async def update_badge_rule(
    badge_type: str,
    data: BadgeRuleUpdateDTO,
    service: Annotated[BadgeRuleService, Depends(badge_rule_service)],
) -> BadgeRuleDTO:
    try:
        rule = await service.get_rule_by_type(badge_type)
        return await service.update_rule(rule.id, data)
    except BadgeRuleNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Badge rule not found")


@router.delete("/badge-rules/{badge_type}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_badge_rule(
    badge_type: str,
    service: Annotated[BadgeRuleService, Depends(badge_rule_service)],
) -> None:
    try:
        rule = await service.get_rule_by_type(badge_type)
        await service.delete_rule(rule.id)
    except BadgeRuleNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Badge rule not found")
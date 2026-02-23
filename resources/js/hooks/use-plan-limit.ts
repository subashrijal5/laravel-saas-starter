import { useMemo } from 'react';
import { useSubscription } from '@/hooks/use-subscription';

export function usePlanLimit(feature: string, currentCount: number = 0) {
    const { limits } = useSubscription();

    return useMemo(() => {
        const limit = limits[feature] ?? null;
        const isUnlimited = limit === null;
        const isWithin = isUnlimited || currentCount < limit;

        return {
            limit,
            isUnlimited,
            isWithin,
            remaining: isUnlimited ? null : Math.max(0, limit - currentCount),
        };
    }, [limits, feature, currentCount]);
}

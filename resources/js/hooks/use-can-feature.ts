import { usePlanLimit } from '@/hooks/use-plan-limit';

export function useCanFeature(feature: string, currentCount: number = 0) {
    const { isWithin } = usePlanLimit(feature, currentCount);

    return isWithin;
}

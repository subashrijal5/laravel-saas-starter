import { Badge } from '@/components/ui/badge';
import { useSubscription } from '@/hooks/use-subscription';

export default function PlanBadge() {
    const { planName, isOnTrial } = useSubscription();

    return (
        <Badge variant={isOnTrial ? 'outline' : 'secondary'}>
            {planName}
            {isOnTrial && ' (Trial)'}
        </Badge>
    );
}
